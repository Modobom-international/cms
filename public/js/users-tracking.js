/*!
 * Users tracking behavior script with backend-verified exclusion
 * Copyright 2025 Modobom
 * Licensed under MIT
 */

(function () {
    "use strict";
    const userUUID = getUserUUID();
    const isMobile = window.innerWidth <= 768;
    const behaviorTimeline = [];
    const aiTrainingData = [];
    let heartbeatInterval;
    let isExcluded = false;

    let mouseMovements = 0;
    let keyPresses = 0;
    let lastInteractionTime = getCurrentTimeInGMT7();
    let userStartTime = getCurrentTimeInGMT7();
    let totalTimeOnsite = 0;
    let startTime = getCurrentTimeInGMT7();

    async function verifyExcludeToken() {
        const token = localStorage.getItem('excludeToken');
        if (!token) {
            isExcluded = false;
            return;
        }

        try {
            const response = await fetch(`${checkURL()}/api/verify-exclude-token`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ token: token }),
            });
            const data = await response.json();
            isExcluded = data.valid === true;
        } catch (error) {
            console.error('Error verifying token:', error);
            isExcluded = false;
        }
    }

    verifyExcludeToken().then(() => {
        if (!isExcluded) startHeartbeat();
    });

    function isBot() {
        const currentTime = getCurrentTimeInGMT7();
        const timeSinceLastInteraction = currentTime - lastInteractionTime;
        const userAgent = navigator.userAgent.toLowerCase();
        const botPatterns = [
            /bot/i, /spider/i, /crawler/i, /slurp/i, /googlebot/i,
            /bingbot/i, /yandexbot/i, /duckduckbot/i, /baiduspider/i,
            /facebot/i, /ia_archiver/i, /headless/i
        ];

        const isSuspicious = (
            timeSinceLastInteraction < 50 ||
            mouseMovements === 0 && keyPresses > 10 ||
            navigator.webdriver ||
            !window.outerWidth ||
            performance.timing.domInteractive < 100
        );

        const botDetected = botPatterns.some(pattern => pattern.test(userAgent)) || isSuspicious;
        lastInteractionTime = currentTime;
        return botDetected;
    }

    function shouldTrack() {
        return !isExcluded && !isBot();
    }

    function getElementDetails(element) {
        return {
            tagName: element.tagName,
            id: element.id || '',
            classes: element.className ? Array.from(element.classList) : [],
            textContent: element.textContent ? element.textContent.trim().substring(0, 100) : '',
            attributes: getAttributes(element),
            position: {
                x: element.getBoundingClientRect().left + window.scrollX,
                y: element.getBoundingClientRect().top + window.scrollY,
                width: element.offsetWidth,
                height: element.offsetHeight
            },
            parentTag: element.parentElement ? element.parentElement.tagName : '',
            isButton: element.tagName === 'BUTTON' || element.type === 'button' || element.classList.contains('btn'),
            isInput: ['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName)
        };
    }

    function getAttributes(element) {
        const attrs = {};
        if (element.attributes) {
            for (let attr of element.attributes) {
                attrs[attr.name] = attr.value;
            }
        }
        return attrs;
    }

    function startHeartbeat() {
        if (shouldTrack()) {
            sendHeartbeat();
            heartbeatInterval = setInterval(() => {
                if (shouldTrack()) {
                    sendHeartbeat();
                } else {
                    clearInterval(heartbeatInterval);
                }
            }, 10000);
        }
    }

    function sendHeartbeat() {
        const heartbeatData = {
            uuid: userUUID,
            timestamp: formatDate(),
            domain: window.location.hostname,
            path: window.location.pathname + window.location.search,
            userInfo: getUserInfo()
        };
        sendDataToServer(heartbeatData, '/heartbeat');
    }

    function aggregateForVideo(eventName, eventData, timestamp) {
        if (shouldTrack()) {
            behaviorTimeline.push({
                time: timestamp,
                event: eventName,
                data: {
                    ...eventData,
                    screenWidth: window.innerWidth,

                    screenHeight: window.innerHeight,
                    scrollHeight: document.body.scrollHeight || document.documentElement.scrollHeight
                }
            });
        }
    }

    function aggregateForAITraining(eventName, eventData, timestamp) {
        if (shouldTrack()) {
            const pageContext = {
                title: document.title,
                url: window.location.href,
                visibleText: document.body.innerText.substring(0, 500),
                elementsCount: document.getElementsByTagName('*').length
            };

            const trainingEntry = {
                timestamp: timestamp,
                eventName: eventName,
                eventData: eventData,
                userUUID: userUUID,
                isBot: isBot(),
                pageContext: pageContext,
                session: {
                    startTime: userStartTime,
                    timeOnPage: totalTimeOnsite,
                    mouseMovements: mouseMovements,
                    keyPresses: keyPresses
                },
                label: inferUserIntent(eventName, eventData)
            };

            aiTrainingData.push(trainingEntry);
        }
    }

    function inferUserIntent(eventName, eventData) {
        if (eventName === 'click' && eventData.elementDetails?.isButton) return 'button_interaction';
        if (eventName === 'click' && eventData.isInternalLink) return 'navigation';
        if (eventName === 'click' && eventData.isLassoButton) return 'conversion';
        if (eventName === 'keydown' && eventData.elementDetails?.isInput) return 'form_interaction';
        if (eventName === 'scroll' && eventData.scrollTop > 500) return 'content_exploration';
        return 'general_interaction';
    }

    function sendDataOnExit() {
        if (shouldTrack()) {
            clearInterval(heartbeatInterval);
            if (behaviorTimeline.length > 0) {
                const videoData = {
                    uuid: userUUID,
                    domain: window.location.hostname,
                    path: window.location.pathname + window.location.search,
                    startTime: userStartTime,
                    endTime: getCurrentTimeInGMT7(),
                    totalTime: totalTimeOnsite,
                    timeline: behaviorTimeline,
                    userInfo: getUserInfo()
                };
                sendDataToServer(videoData, '/create-video-timeline');
            }

            if (aiTrainingData.length > 0) {
                const trainingData = {
                    uuid: userUUID,
                    domain: window.location.hostname,
                    sessionStart: userStartTime,
                    sessionEnd: getCurrentTimeInGMT7(),
                    events: aiTrainingData
                };
                sendDataToServer(trainingData, '/collect-ai-training-data');
            }
        }
    }

    function updateTimeOnsite() {
        const currentTime = getCurrentTimeInGMT7();
        totalTimeOnsite += 's' + currentTime - startTime;
        startTime = currentTime;
    }

    window.addEventListener('focus', () => {
        startTime = Date.now();
        const timestamp = formatDate();
        aggregateForVideo('window_focus', {}, timestamp);
        aggregateForAITraining('window_focus', {}, timestamp);
    });

    window.addEventListener('blur', () => {
        updateTimeOnsite();
        const timestamp = formatDate();
        aggregateForVideo('window_blur', {}, timestamp);
        aggregateForAITraining('window_blur', {}, timestamp);
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        const height = document.body.scrollHeight || document.documentElement.scrollHeight;
        let eventData = {
            x: event.pageX,
            y: event.pageY,
            elementDetails: getElementDetails(target),
            href: target.href || '',
            isInternalLink: target.href && target.href.includes(window.location.origin),
            isLassoButton: target.classList.contains('lasso-button'),
            lassoButtonLink: target.dataset.lassoLink || '',
            device: isMobile ? 'mobile' : 'desktop',
            height: height
        };

        const timestamp = formatDate();
        recordEvent('click', eventData);
        aggregateForVideo('click', eventData, timestamp);
        aggregateForAITraining('click', eventData, timestamp);
    });

    document.addEventListener('mousemove', throttle((event) => {
        const target = event.target;
        const x = event.pageX;
        const y = event.pageY;
        const device = isMobile ? 'mobile' : 'desktop';
        const height = document.body.scrollHeight || document.documentElement.scrollHeight;
        mouseMovements++;

        const eventData = {
            x, y,
            mouseMovements,
            elementDetails: getElementDetails(target),
            device,
            height
        };
        const timestamp = formatDate();
        recordEvent('mousemove', eventData);
        aggregateForVideo('mousemove', eventData, timestamp);
        aggregateForAITraining('mousemove', eventData, timestamp);
    }, 100));

    document.addEventListener('scroll', throttle(() => {
        const scrollTop = window.scrollY;
        const scrollLeft = window.scrollX;
        const device = isMobile ? 'mobile' : 'desktop';

        const eventData = { scrollTop, scrollLeft, device };
        const timestamp = formatDate();
        recordEvent('scroll', eventData);
        aggregateForVideo('scroll', eventData, timestamp);
        aggregateForAITraining('scroll', eventData, timestamp);
    }, 100));

    document.addEventListener('keydown', (event) => {
        keyPresses++;
        const target = event.target;
        const eventData = {
            elementDetails: getElementDetails(target),
            value: target.value || '',
            key: event.key,
            device: isMobile ? 'mobile' : 'desktop'
        };
        const timestamp = formatDate();
        recordEvent('keydown', eventData);
        aggregateForVideo('keydown', eventData, timestamp);
        aggregateForAITraining('keydown', eventData, timestamp);
    });

    window.addEventListener('beforeunload', () => {
        updateTimeOnsite();
        sendDataOnExit();
    });

    function throttle(func, limit) {
        let inThrottle;
        return function (...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    function sendDataToServer(data, endpoint) {
        if (shouldTrack()) {
            let url = checkURL() + endpoint;
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            }).catch(error => console.error('Error:', error));
        }
    }

    function recordEvent(eventName, eventData) {
        const path = window.location.pathname + window.location.search;
        const timestamp = formatDate();
        const event = {
            eventName: eventName,
            eventData: eventData,
            timestamp: timestamp,
            user: getUserInfo(),
            domain: window.location.hostname,
            uuid: userUUID,
            path: path
        };

        if (shouldTrack()) {
            sendDataToServer(event);
        }
    }

    function getUserInfo() {
        function getBrowserInfo() {
            const ua = navigator.userAgent;
            let browser = 'Unknown';
            let version = '';
            if (/chrome|crios/i.test(ua)) {
                browser = 'Chrome';
                version = ua.match(/(?:chrome|crios)\/(\d+\.\d+)/i)?.[1] || '';
            } else if (/firefox|fxios/i.test(ua)) {
                browser = 'Firefox';
                version = ua.match(/(?:firefox|fxios)\/(\d+\.\d+)/i)?.[1] || '';
            } else if (/safari/i.test(ua) && !/chrome/i.test(ua)) {
                browser = 'Safari';
                version = ua.match(/version\/(\d+\.\d+)/i)?.[1] || '';
            } else if (/edg/i.test(ua)) {
                browser = 'Edge';
                version = ua.match(/edg\/(\d+\.\d+)/i)?.[1] || '';
            }
            return { name: browser, version: version };
        }

        const basicInfo = {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            languages: navigator.languages ? Array.from(navigator.languages) : [],
            cookiesEnabled: navigator.cookieEnabled,
            screenWidth: window.screen.width,
            screenHeight: window.screen.height,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            performance: performance.timing.toJSON(),
        };

        const additionalInfo = {
            browser: getBrowserInfo(),
            windowSize: {
                width: window.innerWidth,
                height: window.innerHeight
            },
            connection: navigator.connection ? {
                effectiveType: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                rtt: navigator.connection.rtt
            } : null,
            touchSupport: navigator.maxTouchPoints > 0,
            webGLSupport: !!window.WebGLRenderingContext && !!document.createElement('canvas').getContext('webgl'),
            referrer: document.referrer || 'Direct',
            pageLoadTime: performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart
        };

        let geolocation = null;
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    geolocation = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };
                },
                (error) => {
                    console.warn('Geolocation error:', error.message);
                },
                { timeout: 5000 }
            );
        }

        let batteryInfo = null;
        if (navigator.getBattery) {
            navigator.getBattery().then(battery => {
                batteryInfo = {
                    level: battery.level * 100,
                    charging: battery.charging,
                    chargingTime: battery.chargingTime,
                    dischargingTime: battery.dischargingTime
                };
            });
        }

        return {
            ...basicInfo,
            ...additionalInfo,
            geolocation: geolocation,
            battery: batteryInfo
        };
    }

    function checkURL() {
        const hostname = window.location.hostname;
        return (hostname === 'localhost' || hostname === '127.0.0.1')
            ? 'http://127.0.0.1:8000'
            : 'https://apkhype.com';
    }

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    function getUserUUID() {
        let uuid = localStorage.getItem('userUUID');
        if (!uuid) {
            uuid = generateUUID();
            localStorage.setItem('userUUID', uuid);
        }
        return uuid;
    }

    function formatDate() {
        const now = new Date();
        const utcTime = now.getTime() + (now.getTimezoneOffset() * 60000);
        const gmt7Time = new Date(utcTime + (7 * 60 * 60 * 1000));
        return gmt7Time.toISOString();
    }

    function getCurrentTimeInGMT7() {
        const now = new Date();
        const utcTime = now.getTime() + (now.getTimezoneOffset() * 60000);
        return new Date(utcTime + (7 * 60 * 60 * 1000)).getTime();
    }
})();