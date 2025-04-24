(function () {
    "use strict";

    let userConsented = false;
    const userUUID = getUserUUID();
    const isMobile = window.innerWidth <= 768;
    const behaviorTimeline = [];
    let heartbeatInterval;
    let mouseMovements = 0;
    let keyPresses = 0;
    let userStartTime = getCurrentTimeInGMT7();
    let totalTimeOnsite = 0;
    let startTime = getCurrentTimeInGMT7();

    const translations = {
        'vi': {
            title: 'Thông Báo Quyền Riêng Tư',
            message: 'Chúng tôi sử dụng dữ liệu của bạn để cải thiện trải nghiệm và tối ưu quảng cáo. Bạn có đồng ý không?',
            decline: 'Từ Chối',
            accept: 'Đồng Ý'
        },
        'en': {
            title: 'Privacy Notice',
            message: 'We use your data to improve your experience and optimize ads. Do you agree?',
            decline: 'Decline',
            accept: 'Accept'
        },
        'ro': {
            title: 'Notificare de Confidențialitate',
            message: 'Folosim datele tale pentru a îmbunătăți experiența și a optimiza reclamele. Ești de acord?',
            decline: 'Refuz',
            accept: 'Accept'
        },
        'th': {
            title: 'ประกาศความเป็นส่วนตัว',
            message: 'เราใช้ข้อมูลของคุณเพื่อปรับปรุงประสบการณ์และเพิ่มประสิทธิภาพโฆษณา คุณตกลงหรือไม่?',
            decline: 'ปฏิเสธ',
            accept: 'ยอมรับ'
        }
    };

    function getUserLanguage() {
        const userLang = navigator.language || navigator.userLanguage || 'en';
        const langCode = userLang.split('-')[0];
        return translations[langCode] ? langCode : 'en';
    }

    function showConsentPopup() {
        const lang = getUserLanguage();
        const translation = translations[lang];

        const consentPopup = document.createElement('div');
        consentPopup.style.position = 'fixed';
        consentPopup.style.top = '0';
        consentPopup.style.left = '0';
        consentPopup.style.width = '100%';
        consentPopup.style.height = '100%';
        consentPopup.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        consentPopup.style.display = 'flex';
        consentPopup.style.justifyContent = 'center';
        consentPopup.style.alignItems = 'center';
        consentPopup.style.zIndex = '1000';

        consentPopup.innerHTML = `
            <div style="background: white; padding: 20px; border-radius: 8px; max-width: 400px; text-align: center;">
                <h2>${translation.title}</h2>
                <p>${translation.message}</p>
                <button id="consentDecline" style="margin: 10px; padding: 10px 20px; background: #ccc; border: none; cursor: pointer;">${translation.decline}</button>
                <button id="consentAccept" style="margin: 10px; padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">${translation.accept}</button>
            </div>
        `;

        document.body.appendChild(consentPopup);

        document.getElementById('consentAccept').addEventListener('click', () => {
            userConsented = true;
            localStorage.setItem('userConsent', 'true');
            document.body.removeChild(consentPopup);
            startTracking();
        });

        document.getElementById('consentDecline').addEventListener('click', () => {
            userConsented = false;
            localStorage.setItem('userConsent', 'false');
            document.body.removeChild(consentPopup);
        });
    }

    function checkConsent() {
        const consent = localStorage.getItem('userConsent');
        if (consent === 'true') {
            userConsented = true;
            startTracking();
        } else if (consent === 'false') {
            userConsented = false;
        } else {
            showConsentPopup();
        }
    }

    function isBot() {
        const userAgent = navigator.userAgent.toLowerCase();
        const botPatterns = [
            /bot/i, /spider/i, /crawler/i, /slurp/i, /googlebot/i,
            /bingbot/i, /yandexbot/i, /duckduckbot/i, /baiduspider/i,
            /facebot/i, /ia_archiver/i, /headless/i
        ];

        const isSuspicious = (
            navigator.webdriver ||
            !window.outerWidth ||
            performance.timing.domInteractive < 100
        );

        return botPatterns.some(pattern => pattern.test(userAgent)) || isSuspicious;
    }

    function shouldTrack() {
        return userConsented && !isBot();
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

        function getTrafficSource(referrer) {
            if (!referrer || referrer === '') return 'Direct';
            const url = new URL(referrer);
            const hostname = url.hostname.toLowerCase();
            if (hostname.includes('google.')) return 'Google Search';
            else if (hostname.includes('facebook.com')) return 'Facebook';
            else if (hostname.includes('twitter.com') || hostname.includes('x.com')) return 'Twitter';
            else return 'Other';
        }

        return {
            userAgent: navigator.userAgent,
            browser: getBrowserInfo(),
            windowSize: { width: window.innerWidth, height: window.innerHeight },
            referrer: document.referrer || 'Direct',
            trafficSource: getTrafficSource(document.referrer),
            pageLoadTime: performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart
        };
    }

    function getElementDetails(element) {
        return {
            tagName: element.tagName,
            id: element.id || '',
            classes: element.className ? Array.from(element.classList) : [],
            textContent: element.textContent ? element.textContent.trim().substring(0, 100) : '',
            position: {
                x: element.getBoundingClientRect().left + window.scrollX,
                y: element.getBoundingClientRect().top + window.scrollY,
                width: element.offsetWidth,
                height: element.offsetHeight
            },
            isButton: element.tagName === 'BUTTON' || element.type === 'button' || element.classList.contains('btn'),
            isInput: ['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName)
        };
    }

    function startHeartbeat() {
        sendHeartbeat();
        heartbeatInterval = setInterval(() => {
            if (shouldTrack()) {
                sendHeartbeat();
            } else {
                clearInterval(heartbeatInterval);
            }
        }, 5000);
    }

    function sendHeartbeat() {
        const heartbeatData = {
            uuid: userUUID,
            timestamp: formatDate(),
            domain: window.location.hostname,
            path: window.location.pathname + window.location.search,
            userInfo: getUserInfo()
        };
        sendDataToServer(heartbeatData, '/api/heartbeat');
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

    function sendDataOnExit() {
        if (shouldTrack() && behaviorTimeline.length > 0) {
            clearInterval(heartbeatInterval);
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
            sendDataToServer(videoData, '/api/create-video-timeline');
        }
    }

    function updateTimeOnsite() {
        const currentTime = getCurrentTimeInGMT7();
        totalTimeOnsite += currentTime - startTime;
        startTime = currentTime;
    }

    function startTracking() {
        window.addEventListener('focus', () => {
            startTime = Date.now();
            const timestamp = formatDate();
            aggregateForVideo('window_focus', {}, timestamp);
        });

        window.addEventListener('blur', () => {
            updateTimeOnsite();
            const timestamp = formatDate();
            aggregateForVideo('window_blur', {}, timestamp);
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
                device: isMobile ? 'mobile' : 'desktop',
                height: height
            };

            const timestamp = formatDate();
            recordEvent('click', eventData);
            aggregateForVideo('click', eventData, timestamp);
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
        }, 100));

        document.addEventListener('scroll', throttle(() => {
            const scrollTop = window.scrollY;
            const scrollLeft = window.scrollX;
            const device = isMobile ? 'mobile' : 'desktop';

            const eventData = { scrollTop, scrollLeft, device };
            const timestamp = formatDate();
            recordEvent('scroll', eventData);
            aggregateForVideo('scroll', eventData, timestamp);
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
        });

        window.addEventListener('beforeunload', () => {
            updateTimeOnsite();
            sendDataOnExit();
        });

        startHeartbeat();
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
            }).catch(error => console.error('Error sending data to server:', error));
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
            sendDataToServer(event, '/api/tracking-event');
        }
    }

    function checkURL() {
        const hostname = window.location.hostname;
        return (hostname === 'localhost' || hostname === '127.0.0.1')
            ? 'http://127.0.0.1:8000'
            : 'https://api.modobomco.com';
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

    window.addEventListener('load', checkConsent);
})();