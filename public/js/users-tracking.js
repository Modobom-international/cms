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
        'en': {
            title: 'Privacy Notice',
            message: 'We collect information about your usage behavior, device, and referrer for analytics, experience optimization, and security. You can accept or reject.',
            details: 'Collected data: clicks, mouse movements, device, browser, referrer, session time. Purpose: analytics, optimization, security.',
            accept: 'Accept',
            reject: 'Reject',
        },
        'fr': {
            title: 'Avis de confidentialité',
            message: 'Nous collectons des informations sur votre comportement d\'utilisation, votre appareil et votre référent pour l\'analyse, l\'optimisation de l\'expérience et la sécurité. Vous pouvez accepter ou refuser.',
            details: 'Données collectées : clics, mouvements de souris, appareil, navigateur, référent, durée de session. Objectif : analyse, optimisation, sécurité.',
            accept: 'Accepter',
            reject: 'Refuser',
        },
        'de': {
            title: 'Datenschutzhinweis',
            message: 'Wir erfassen Informationen über Ihr Nutzungsverhalten, Ihr Gerät und den Referrer für Analysen, Optimierung und Sicherheit. Sie können akzeptieren oder ablehnen.',
            details: 'Gesammelte Daten: Klicks, Mausbewegungen, Gerät, Browser, Referrer, Sitzungsdauer. Zweck: Analyse, Optimierung, Sicherheit.',
            accept: 'Akzeptieren',
            reject: 'Ablehnen',
        },
        'it': {
            title: 'Informativa sulla privacy',
            message: 'Raccogliamo informazioni sul comportamento di utilizzo, dispositivo e referrer per analisi, ottimizzazione e sicurezza. Puoi accettare o rifiutare.',
            details: 'Dati raccolti: clic, movimenti del mouse, dispositivo, browser, referrer, durata sessione. Scopo: analisi, ottimizzazione, sicurezza.',
            accept: 'Accetta',
            reject: 'Rifiuta',
        },
        'es': {
            title: 'Aviso de privacidad',
            message: 'Recopilamos información sobre su comportamiento de uso, dispositivo y referencia para análisis, optimización y seguridad. Puede aceptar o rechazar.',
            details: 'Datos recopilados: clics, movimientos del ratón, dispositivo, navegador, referencia, tiempo de sesión. Propósito: análisis, optimización, seguridad.',
            accept: 'Aceptar',
            reject: 'Rechazar',
        },
        'ro': {
            title: 'Notificare de Confidențialitate',
            message: 'Colectăm informații despre comportamentul de utilizare, dispozitiv și sursa de acces pentru analiză, optimizare și securitate. Puteți accepta sau respinge.',
            details: 'Date colectate: click-uri, mișcări mouse, dispozitiv, browser, referrer, timp sesiune. Scop: analiză, optimizare, securitate.',
            accept: 'Acceptă',
            reject: 'Respinge',
        },
        'pl': {
            title: 'Informacja o prywatności',
            message: 'Zbieramy informacje o Twoim zachowaniu, urządzeniu i źródle wejścia w celach analitycznych, optymalizacyjnych i bezpieczeństwa. Możesz zaakceptować lub odrzucić.',
            details: 'Zbierane dane: kliknięcia, ruchy myszą, urządzenie, przeglądarka, referrer, czas sesji. Cel: analiza, optymalizacja, bezpieczeństwo.',
            accept: 'Akceptuj',
            reject: 'Odrzuć',
        },
        'nl': {
            title: 'Privacyverklaring',
            message: 'We verzamelen informatie over uw gebruiksgedrag, apparaat en referrer voor analyse, optimalisatie en beveiliging. U kunt accepteren of weigeren.',
            details: 'Verzamelde gegevens: klikken, muisbewegingen, apparaat, browser, referrer, sessieduur. Doel: analyse, optimalisatie, beveiliging.',
            accept: 'Accepteren',
            reject: 'Weigeren',
        },
        'sv': {
            title: 'Integritetsmeddelande',
            message: 'Vi samlar in information om ditt användarbeteende, enhet och hänvisning för analys, optimering och säkerhet. Du kan acceptera eller avvisa.',
            details: 'Insamlade data: klick, musrörelser, enhet, webbläsare, hänvisning, sessionstid. Syfte: analys, optimering, säkerhet.',
            accept: 'Acceptera',
            reject: 'Avvisa',
        },
        'da': {
            title: 'Privatlivsmeddelelse',
            message: 'Vi indsamler oplysninger om din brug, enhed og henviser til analyse, optimering og sikkerhed. Du kan acceptere eller afvise.',
            details: 'Indsamlede data: klik, musebevægelser, enhed, browser, henviser, sessionstid. Formål: analyse, optimering, sikkerhed.',
            accept: 'Accepter',
            reject: 'Afvis',
        },
        'fi': {
            title: 'Tietosuojailmoitus',
            message: 'Keräämme tietoja käyttötavastasi, laitteestasi ja viittaajasta analysointia, optimointia ja turvallisuutta varten. Voit hyväksyä tai hylätä.',
            details: 'Kerätyt tiedot: napsautukset, hiiren liikkeet, laite, selain, viittaaja, istunnon kesto. Tarkoitus: analyysi, optimointi, turvallisuus.',
            accept: 'Hyväksy',
            reject: 'Hylkää',
        }
    };

    function isEUCountry(countryCode) {
        const euCountries = [
            'AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','UK','GB'
        ];
        return euCountries.includes(countryCode);
    }

    function getUserCountry(callback) {
        fetch('https://ipapi.co/json/')
            .then(response => response.json())
            .then(data => {
                callback(data.country_code);
            })
            .catch(() => {
                callback('EU');
            });
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

    function getUserLanguage() {
        const userLang = navigator.language || navigator.userLanguage || 'en';
        const langCode = userLang.split('-')[0];
        return Object.keys(translations).includes(langCode) ? langCode : 'en';
    }

    function showConsentPopup(onAccept, onReject) {
        const lang = getUserLanguage();
        const t = translations[lang];
        const consentPopup = document.createElement('div');
        consentPopup.style.position = 'fixed';
        consentPopup.style.bottom = '10px';
        consentPopup.style.left = '10px';
        consentPopup.style.right = '10px';
        consentPopup.style.backgroundColor = '#f5f5f5';
        consentPopup.style.padding = '20px';
        consentPopup.style.borderRadius = '8px';
        consentPopup.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        consentPopup.style.maxWidth = '95vw';
        consentPopup.style.zIndex = '1000';
        consentPopup.style.fontFamily = 'Arial, sans-serif';
        consentPopup.style.margin = '0 auto';
        consentPopup.style.width = 'calc(100vw - 20px)';
        consentPopup.innerHTML = `
            <div style="text-align:center;">
                <h2 style="font-size:18px; margin:0 0 10px 0;">${t.title}</h2>
                <p style="font-size:14px; margin-bottom:8px;">${t.message}</p>
                <p style="font-size:12px; color:#555; margin-bottom:16px;">${t.details}</p>
                <button id="consent-accept" style="padding:10px 20px; background:#007bff; color:#fff; border:none; border-radius:4px; margin-right:10px; cursor:pointer;">${t.accept}</button>
                <button id="consent-reject" style="padding:10px 20px; background:#f0f0f0; color:#333; border:none; border-radius:4px; cursor:pointer;">${t.reject}</button>
            </div>
        `;
        document.body.appendChild(consentPopup);
        document.getElementById('consent-accept').onclick = function() {
            document.body.removeChild(consentPopup);
            onAccept();
        };
        document.getElementById('consent-reject').onclick = function() {
            document.body.removeChild(consentPopup);
            onReject();
        };
    }

    window.addEventListener('load', function() {
        getUserCountry(function(countryCode) {
            if (isEUCountry(countryCode)) {
                showConsentPopup(
                    function() {
                        userConsented = true;
                        analyticsEnabled = true;
                        personalizationEnabled = true;
                        startTracking();
                    },
                    function() {
                        userConsented = false;
                        analyticsEnabled = false;
                        personalizationEnabled = false;
                    }
                );
            } else {
                userConsented = true;
                analyticsEnabled = true;
                personalizationEnabled = true;
                startTracking();
            }
        });
    });
})();