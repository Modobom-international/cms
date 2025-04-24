(function () {
    "use strict";

    const userUUID = getUserUUID();
    const isMobile = window.innerWidth <= 768;
    const behaviorTimeline = [];

    let userConsented = false;
    let analyticsEnabled = true;
    let personalizationEnabled = true;
    let heartbeatInterval;
    let mouseMovements = 0;
    let keyPresses = 0;
    let userStartTime = getCurrentTimeInGMT7();
    let totalTimeOnsite = 0;
    let startTime = getCurrentTimeInGMT7();

    const translations = {
        'vi': {
            title: 'Thông Báo Quyền Riêng Tư',
            message: 'Trang web này sử dụng công nghệ như cookies để hỗ trợ các chức năng cần thiết, phân tích, và cá nhân hóa. Bạn có thể thay đổi cài đặt bất kỳ lúc nào hoặc chấp nhận cài đặt mặc định.',
            closeMessage: 'Bạn có thể đóng thanh này để tiếp tục với các cookies cần thiết.',
            privacyNotice: 'Thông Báo Quyền Riêng Tư',
            analytics: 'Phân Tích',
            personalization: 'Cá Nhân Hóa',
            save: 'Lưu',
            acceptAll: 'Chấp Nhận Tất Cả',
            rejectAll: 'Từ Chối Tất Cả'
        },
        'en': {
            title: 'Privacy Notice',
            message: 'This website utilizes technologies such as cookies to enable essential site functionality, analytics, and personalization. You may change your settings at any time or accept the default settings.',
            closeMessage: 'You may close this banner to continue with only essential cookies.',
            privacyNotice: 'Privacy Notice',
            analytics: 'Analytics',
            personalization: 'Personalization',
            save: 'Save',
            acceptAll: 'Accept All',
            rejectAll: 'Reject All'
        },
        'ro': {
            title: 'Notificare de Confidențialitate',
            message: 'Acest site web utilizează tehnologii precum cookie-urile pentru a activa funcționalitățile esențiale, analize și personalizare. Puteți schimba setările în orice moment sau accepta setările implicite.',
            closeMessage: 'Puteți închide această bară pentru a continua doar cu cookie-urile esențiale.',
            privacyNotice: 'Notificare de Confidențialitate',
            analytics: 'Analize',
            personalization: 'Personalizare',
            save: 'Salvează',
            acceptAll: 'Acceptă Toate',
            rejectAll: 'Respinge Toate'
        },
        'th': {
            title: 'ประกาศความเป็นส่วนตัว',
            message: 'เว็บไซต์นี้ใช้เทคโนโลยีเช่นคุกกี้เพื่อเปิดใช้งานฟังก์ชันที่จำเป็น การวิเคราะห์ และการปรับแต่งส่วนบุคคล คุณสามารถเปลี่ยนการตั้งค่าได้ทุกเมื่อหรือยอมรับการตั้งค่าเริ่มต้น',
            closeMessage: 'คุณสามารถปิดแบนเนอร์นี้เพื่อดำเนินการต่อด้วยคุกกี้ที่จำเป็นเท่านั้น',
            privacyNotice: 'ประกาศความเป็นส่วนตัว',
            analytics: 'การวิเคราะห์',
            personalization: 'การปรับแต่งส่วนบุคคล',
            save: 'บันทึก',
            acceptAll: 'ยอมรับทั้งหมด',
            rejectAll: 'ปฏิเสธทั้งหมด'
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
        consentPopup.style.top = '10px';
        consentPopup.style.right = '10px';
        consentPopup.style.backgroundColor = '#f5f5f5';
        consentPopup.style.padding = '20px';
        consentPopup.style.borderRadius = '8px';
        consentPopup.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        consentPopup.style.maxWidth = '400px';
        consentPopup.style.zIndex = '1000';
        consentPopup.style.fontFamily = 'Arial, sans-serif';

        consentPopup.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-size: 18px; margin: 0;">${translation.title}</h2>
                <button id="closePopup" style="background: none; border: none; font-size: 18px; cursor: pointer;">✕</button>
            </div>
            <p style="font-size: 14px; margin-bottom: 10px;">${translation.message}</p>
            <p style="font-size: 14px; margin-bottom: 20px;">${translation.closeMessage} <a href="#" style="color: #007bff; text-decoration: none;">${translation.privacyNotice}</a></p>
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-size: 14px;">${translation.analytics}</span>
                    <label style="display: inline-flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="analyticsToggle" style="display: none;" ${analyticsEnabled ? 'checked' : ''}>
                        <div style="width: 40px; height: 20px; background-color: ${analyticsEnabled ? '#007bff' : '#ccc'}; border-radius: 20px; position: relative; transition: background-color 0.3s;">
                            <div style="width: 16px; height: 16px; background-color: white; border-radius: 50%; position: absolute; top: 2px; left: ${analyticsEnabled ? '22px' : '2px'}; transition: left 0.3s;"></div>
                        </div>
                    </label>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 14px;">${translation.personalization}</span>
                    <label style="display: inline-flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="personalizationToggle" style="display: none;" ${personalizationEnabled ? 'checked' : ''}>
                        <div style="width: 40px; height: 20px; background-color: ${personalizationEnabled ? '#007bff' : '#ccc'}; border-radius: 20px; position: relative; transition: background-color 0.3s;">
                            <div style="width: 16px; height: 16px; background-color: white; border-radius: 50%; position: absolute; top: 2px; left: ${personalizationEnabled ? '22px' : '2px'}; transition: left 0.3s;"></div>
                        </div>
                    </label>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button id="saveSettings" style="padding: 10px; background-color: #f0f0f0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">${translation.save}</button>
                <button id="acceptAll" style="padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">${translation.acceptAll}</button>
                <button id="rejectAll" style="padding: 10px; background-color: #f0f0f0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">${translation.rejectAll}</button>
            </div>
        `;

        document.body.appendChild(consentPopup);

        const analyticsToggle = document.getElementById('analyticsToggle');
        const analyticsToggleSwitch = analyticsToggle.nextElementSibling;
        analyticsToggle.addEventListener('change', () => {
            analyticsEnabled = analyticsToggle.checked;
            analyticsToggleSwitch.style.backgroundColor = analyticsEnabled ? '#007bff' : '#ccc';
            analyticsToggleSwitch.firstElementChild.style.left = analyticsEnabled ? '22px' : '2px';
        });

        const personalizationToggle = document.getElementById('personalizationToggle');
        const personalizationToggleSwitch = personalizationToggle.nextElementSibling;
        personalizationToggle.addEventListener('change', () => {
            personalizationEnabled = personalizationToggle.checked;
            personalizationToggleSwitch.style.backgroundColor = personalizationEnabled ? '#007bff' : '#ccc';
            personalizationToggleSwitch.firstElementChild.style.left = personalizationEnabled ? '22px' : '2px';
        });

        document.getElementById('saveSettings').addEventListener('click', () => {
            userConsented = true;
            localStorage.setItem('userConsent', 'true');
            localStorage.setItem('analyticsEnabled', analyticsEnabled.toString());
            localStorage.setItem('personalizationEnabled', personalizationEnabled.toString());
            document.body.removeChild(consentPopup);
            startTracking();
        });

        document.getElementById('acceptAll').addEventListener('click', () => {
            userConsented = true;
            analyticsEnabled = true;
            personalizationEnabled = true;
            localStorage.setItem('userConsent', 'true');
            localStorage.setItem('analyticsEnabled', 'true');
            localStorage.setItem('personalizationEnabled', 'true');
            document.body.removeChild(consentPopup);
            startTracking();
        });

        document.getElementById('rejectAll').addEventListener('click', () => {
            userConsented = false;
            analyticsEnabled = false;
            personalizationEnabled = false;
            localStorage.setItem('userConsent', 'false');
            localStorage.setItem('analyticsEnabled', 'false');
            localStorage.setItem('personalizationEnabled', 'false');
            document.body.removeChild(consentPopup);
        });

        document.getElementById('closePopup').addEventListener('click', () => {
            userConsented = false;
            analyticsEnabled = false;
            personalizationEnabled = false;
            localStorage.setItem('userConsent', 'false');
            localStorage.setItem('analyticsEnabled', 'false');
            localStorage.setItem('personalizationEnabled', 'false');
            document.body.removeChild(consentPopup);
        });
    }

    function checkConsent() {
        const consent = localStorage.getItem('userConsent');
        const storedAnalytics = localStorage.getItem('analyticsEnabled');
        const storedPersonalization = localStorage.getItem('personalizationEnabled');

        if (consent === 'true') {
            userConsented = true;
            analyticsEnabled = storedAnalytics === 'true';
            personalizationEnabled = storedPersonalization === 'true';
            startTracking();
        } else if (consent === 'false') {
            userConsented = false;
            analyticsEnabled = false;
            personalizationEnabled = false;
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

        const userInfo = {
            userAgent: navigator.userAgent,
            browser: getBrowserInfo(),
            windowSize: { width: window.innerWidth, height: window.innerHeight },
            referrer: document.referrer || 'Direct',
            trafficSource: getTrafficSource(document.referrer),
            pageLoadTime: performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart
        };

        if (analyticsEnabled) {
            userInfo.analytics = {
                pageLoadTime: userInfo.pageLoadTime,
                windowSize: userInfo.windowSize
            };
        }

        if (personalizationEnabled) {
            userInfo.personalization = {
                trafficSource: userInfo.trafficSource,
                referrer: userInfo.referrer
            };
        }

        return userInfo;
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
            if (analyticsEnabled || personalizationEnabled) {
                const eventDetails = { ...eventData };

                if (!analyticsEnabled) {
                    delete eventDetails.screenWidth;
                    delete eventDetails.screenHeight;
                    delete eventDetails.scrollHeight;
                    delete eventDetails.scrollTop;
                    delete eventDetails.scrollLeft;
                }

                if (!personalizationEnabled) {
                    delete eventData.isInternalLink;
                    delete eventData.isLassoButton;
                }
                behaviorTimeline.push({
                    time: timestamp,
                    event: eventName,
                    data: eventDetails
                });
            }
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
                height: height,
                screenWidth: window.innerWidth,
                screenHeight: window.innerHeight,
                scrollHeight: height
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
                height,
                screenWidth: window.innerWidth,
                screenHeight: window.innerHeight,
                scrollHeight: height
            };
            const timestamp = formatDate();
            recordEvent('mousemove', eventData);
            aggregateForVideo('mousemove', eventData, timestamp);
        }, 100));

        document.addEventListener('scroll', throttle(() => {
            const scrollTop = window.scrollY;
            const scrollLeft = window.scrollX;
            const device = isMobile ? 'mobile' : 'desktop';

            const eventData = {
                scrollTop,
                scrollLeft,
                device,
                screenWidth: window.innerWidth,
                screenHeight: window.innerHeight,
                scrollHeight: document.body.scrollHeight || document.documentElement.scrollHeight
            };
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