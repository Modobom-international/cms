/*!
 * Users tracking behavior script with embedded FingerprintJS2
 * Copyright 2025 Modobom
 * Licensed under MIT
 */

!function (e, t, n) { "use strict"; "undefined" != typeof window && "function" == typeof window.define && window.define.amd ? window.define(n) : "undefined" != typeof module && module.exports ? module.exports = n() : t.exports ? t.exports = n() : t.Fingerprint2 = n() }(0, this, function () { "use strict"; var d = function (e, t) { e = [e[0] >>> 16, 65535 & e[0], e[1] >>> 16, 65535 & e[1]], t = [t[0] >>> 16, 65535 & t[0], t[1] >>> 16, 65535 & t[1]]; var n = [0, 0, 0, 0]; return n[3] += e[3] + t[3], n[2] += n[3] >>> 16, n[3] &= 65535, n[2] += e[2] + t[2], n[1] += n[2] >>> 16, n[2] &= 65535, n[1] += e[1] + t[1], n[0] += n[1] >>> 16, n[1] &= 65535, n[0] += e[0] + t[0], n[0] &= 65535, [n[0] << 16 | n[1], n[2] << 16 | n[3]] }, g = function (e, t) { e = [e[0] >>> 16, 65535 & e[0], e[1] >>> 16, 65535 & e[1]], t = [t[0] >>> 16, 65535 & t[0], t[1] >>> 16, 65535 & t[1]]; var n = [0, 0, 0, 0]; return n[3] += e[3] * t[3], n[2] += n[3] >>> 16, n[3] &= 65535, n[2] += e[2] * t[3], n[1] += n[2] >>> 16, n[2] &= 65535, n[2] += e[3] * t[2], n[1] += n[2] >>> 16, n[2] &= 65535, n[1] += e[1] * t[3], n[0] += n[1] >>> 16, n[1] &= 65535, n[1] += e[2] * t[2], n[0] += n[1] >>> 16, n[1] &= 65535, n[1] += e[3] * t[1], n[0] += n[1] >>> 16, n[1] &= 65535, n[0] += e[0] * t[3] + e[1] * t[2] + e[2] * t[1] + e[3] * t[0], n[0] &= 65535, [n[0] << 16 | n[1], n[2] << 16 | n[3]] }, f = function (e, t) { return 32 === (t %= 64) ? [e[1], e[0]] : t < 32 ? [e[0] << t | e[1] >>> 32 - t, e[1] << t | e[0] >>> 32 - t] : (t -= 32, [e[1] << t | e[0] >>> 32 - t, e[0] << t | e[1] >>> 32 - t]) }, h = function (e, t) { return 0 === (t %= 64) ? e : t < 32 ? [e[0] << t | e[1] >>> 32 - t, e[1] << t] : [e[1] << t - 32, 0] }, m = function (e, t) { return [e[0] ^ t[0], e[1] ^ t[1]] }, T = function (e) { return e = m(e, [0, e[0] >>> 1]), e = g(e, [4283543511, 3981806797]), e = m(e, [0, e[0] >>> 1]), e = g(e, [3301882366, 444984403]), e = m(e, [0, e[0] >>> 1]) }, l = function (e, t) { t = t || 0; for (var n = (e = e || "").length % 16, a = e.length - n, r = [0, t], i = [0, t], o = [0, 0], l = [0, 0], s = [2277735313, 289559509], c = [1291169091, 658871167], u = 0; u < a; u += 16)o = [255 & e.charCodeAt(u + 4) | (255 & e.charCodeAt(u + 5)) << 8 | (255 & e.charCodeAt(u + 6)) << 16 | (255 & e.charCodeAt(u + 7)) << 24, 255 & e.charCodeAt(u) | (255 & e.charCodeAt(u + 1)) << 8 | (255 & e.charCodeAt(u + 2)) << 16 | (255 & e.charCodeAt(u + 3)) << 24], l = [255 & e.charCodeAt(u + 12) | (255 & e.charCodeAt(u + 13)) << 8 | (255 & e.charCodeAt(u + 14)) << 16 | (255 & e.charCodeAt(u + 15)) << 24, 255 & e.charCodeAt(u + 8) | (255 & e.charCodeAt(u + 9)) << 8 | (255 & e.charCodeAt(u + 10)) << 16 | (255 & e.charCodeAt(u + 11)) << 24], o = g(o, s), o = f(o, 31), o = g(o, c), r = m(r, o), r = f(r, 27), r = d(r, i), r = d(g(r, [0, 5]), [0, 1390208809]), l = g(l, c), l = f(l, 33), l = g(l, s), i = m(i, l), i = f(i, 31), i = d(i, r), i = d(g(i, [0, 5]), [0, 944331445]); switch (o = [0, 0], l = [0, 0], n) { case 15: l = m(l, h([0, e.charCodeAt(u + 14)], 48)); case 14: l = m(l, h([0, e.charCodeAt(u + 13)], 40)); case 13: l = m(l, h([0, e.charCodeAt(u + 12)], 32)); case 12: l = m(l, h([0, e.charCodeAt(u + 11)], 24)); case 11: l = m(l, h([0, e.charCodeAt(u + 10)], 16)); case 10: l = m(l, h([0, e.charCodeAt(u + 9)], 8)); case 9: l = m(l, [0, e.charCodeAt(u + 8)]), l = g(l, c), l = f(l, 33), l = g(l, s), i = m(i, l); case 8: o = m(o, h([0, e.charCodeAt(u + 7)], 56)); case 7: o = m(o, h([0, e.charCodeAt(u + 6)], 48)); case 6: o = m(o, h([0, e.charCodeAt(u + 5)], 40)); case 5: o = m(o, h([0, e.charCodeAt(u + 4)], 32)); case 4: o = m(o, h([0, e.charCodeAt(u + 3)], 24)); case 3: o = m(o, h([0, e.charCodeAt(u + 2)], 16)); case 2: o = m(o, h([0, e.charCodeAt(u + 1)], 8)); case 1: o = m(o, [0, e.charCodeAt(u)]), o = g(o, s), o = f(o, 31), o = g(o, c), r = m(r, o) }return r = m(r, [0, e.length]), i = m(i, [0, e.length]), r = d(r, i), i = d(i, r), r = T(r), i = T(i), r = d(r, i), i = d(i, r), ("00000000" + (r[0] >>> 0).toString(16)).slice(-8) + ("00000000" + (r[1] >>> 0).toString(16)).slice(-8) + ("00000000" + (i[0] >>> 0).toString(16)).slice(-8) + ("00000000" + (i[1] >>> 0).toString(16)).slice(-8) }, e = { preprocessor: null, audio: { timeout: 1e3, excludeIOS11: !0 }, fonts: { swfContainerId: "fingerprintjs2", swfPath: "flash/compiled/FontList.swf", userDefinedFonts: [], extendedJsFonts: !1 }, screen: { detectScreenOrientation: !0 }, plugins: { sortPluginsFor: [/palemoon/i], excludeIE: !1 }, extraComponents: [], excludes: { enumerateDevices: !0, pixelRatio: !0, doNotTrack: !0, fontsFlash: !0 }, NOT_AVAILABLE: "not available", ERROR: "error", EXCLUDED: "excluded" }, c = function (e, t) { if (Array.prototype.forEach && e.forEach === Array.prototype.forEach) e.forEach(t); else if (e.length === +e.length) for (var n = 0, a = e.length; n < a; n++)t(e[n], n, e); else for (var r in e) e.hasOwnProperty(r) && t(e[r], r, e) }, s = function (e, a) { var r = []; return null == e ? r : Array.prototype.map && e.map === Array.prototype.map ? e.map(a) : (c(e, function (e, t, n) { r.push(a(e, t, n)) }), r) }, n = function () { return navigator.mediaDevices && navigator.mediaDevices.enumerateDevices }, a = function (e) { var t = [window.screen.width, window.screen.height]; return e.screen.detectScreenOrientation && t.sort().reverse(), t }, r = function (e) { if (window.screen.availWidth && window.screen.availHeight) { var t = [window.screen.availHeight, window.screen.availWidth]; return e.screen.detectScreenOrientation && t.sort().reverse(), t } return e.NOT_AVAILABLE }, i = function (e) { if (null == navigator.plugins) return e.NOT_AVAILABLE; for (var t = [], n = 0, a = navigator.plugins.length; n < a; n++)navigator.plugins[n] && t.push(navigator.plugins[n]); return u(e) && (t = t.sort(function (e, t) { return e.name > t.name ? 1 : e.name < t.name ? -1 : 0 })), s(t, function (e) { var t = s(e, function (e) { return [e.type, e.suffixes] }); return [e.name, e.description, t] }) }, o = function (t) { var e = []; if (Object.getOwnPropertyDescriptor && Object.getOwnPropertyDescriptor(window, "ActiveXObject") || "ActiveXObject" in window) { e = s(["AcroPDF.PDF", "Adodb.Stream", "AgControl.AgControl", "DevalVRXCtrl.DevalVRXCtrl.1", "MacromediaFlashPaper.MacromediaFlashPaper", "Msxml2.DOMDocument", "Msxml2.XMLHTTP", "PDF.PdfCtrl", "QuickTime.QuickTime", "QuickTimeCheckObject.QuickTimeCheck.1", "RealPlayer", "RealPlayer.RealPlayer(tm) ActiveX Control (32-bit)", "RealVideo.RealVideo(tm) ActiveX Control (32-bit)", "Scripting.Dictionary", "SWCtl.SWCtl", "Shell.UIHelper", "ShockwaveFlash.ShockwaveFlash", "Skype.Detection", "TDCCtl.TDCCtl", "WMPlayer.OCX", "rmocx.RealPlayer G2 Control", "rmocx.RealPlayer G2 Control.1"], function (e) { try { return new window.ActiveXObject(e), e } catch (e) { return t.ERROR } }) } else e.push(t.NOT_AVAILABLE); return navigator.plugins && (e = e.concat(i(t))), e }, u = function (e) { for (var t = !1, n = 0, a = e.plugins.sortPluginsFor.length; n < a; n++) { var r = e.plugins.sortPluginsFor[n]; if (navigator.userAgent.match(r)) { t = !0; break } } return t }, p = function (t) { try { return !!window.sessionStorage } catch (e) { return t.ERROR } }, v = function (t) { try { return !!window.localStorage } catch (e) { return t.ERROR } }, A = function (t) { try { return !!window.indexedDB } catch (e) { return t.ERROR } }, S = function (e) { return navigator.hardwareConcurrency ? navigator.hardwareConcurrency : e.NOT_AVAILABLE }, C = function (e) { return navigator.cpuClass || e.NOT_AVAILABLE }, B = function (e) { return navigator.platform ? navigator.platform : e.NOT_AVAILABLE }, w = function (e) { return navigator.doNotTrack ? navigator.doNotTrack : navigator.msDoNotTrack ? navigator.msDoNotTrack : window.doNotTrack ? window.doNotTrack : e.NOT_AVAILABLE }, t = function () { var t, e = 0; void 0 !== navigator.maxTouchPoints ? e = navigator.maxTouchPoints : void 0 !== navigator.msMaxTouchPoints && (e = navigator.msMaxTouchPoints); try { document.createEvent("TouchEvent"), t = !0 } catch (e) { t = !1 } return [e, t, "ontouchstart" in window] }, y = function (e) { var t = [], n = document.createElement("canvas"); n.width = 2e3, n.height = 200, n.style.display = "inline"; var a = n.getContext("2d"); return a.rect(0, 0, 10, 10), a.rect(2, 2, 6, 6), t.push("canvas winding:" + (!1 === a.isPointInPath(5, 5, "evenodd") ? "yes" : "no")), a.textBaseline = "alphabetic", a.fillStyle = "#f60", a.fillRect(125, 1, 62, 20), a.fillStyle = "#069", e.dontUseFakeFontInCanvas ? a.font = "11pt Arial" : a.font = "11pt no-real-font-123", a.fillText("Cwm fjordbank glyphs vext quiz, 😃", 2, 15), a.fillStyle = "rgba(102, 204, 0, 0.2)", a.font = "18pt Arial", a.fillText("Cwm fjordbank glyphs vext quiz, 😃", 4, 45), a.globalCompositeOperation = "multiply", a.fillStyle = "rgb(255,0,255)", a.beginPath(), a.arc(50, 50, 50, 0, 2 * Math.PI, !0), a.closePath(), a.fill(), a.fillStyle = "rgb(0,255,255)", a.beginPath(), a.arc(100, 50, 50, 0, 2 * Math.PI, !0), a.closePath(), a.fill(), a.fillStyle = "rgb(255,255,0)", a.beginPath(), a.arc(75, 100, 50, 0, 2 * Math.PI, !0), a.closePath(), a.fill(), a.fillStyle = "rgb(255,0,255)", a.arc(75, 75, 75, 0, 2 * Math.PI, !0), a.arc(75, 75, 25, 0, 2 * Math.PI, !0), a.fill("evenodd"), n.toDataURL && t.push("canvas fp:" + n.toDataURL()), t }, E = function () { var o, e = function (e) { return o.clearColor(0, 0, 0, 1), o.enable(o.DEPTH_TEST), o.depthFunc(o.LEQUAL), o.clear(o.COLOR_BUFFER_BIT | o.DEPTH_BUFFER_BIT), "[" + e[0] + ", " + e[1] + "]" }; if (!(o = F())) return null; var l = [], t = o.createBuffer(); o.bindBuffer(o.ARRAY_BUFFER, t); var n = new Float32Array([-.2, -.9, 0, .4, -.26, 0, 0, .732134444, 0]); o.bufferData(o.ARRAY_BUFFER, n, o.STATIC_DRAW), t.itemSize = 3, t.numItems = 3; var a = o.createProgram(), r = o.createShader(o.VERTEX_SHADER); o.shaderSource(r, "attribute vec2 attrVertex;varying vec2 varyinTexCoordinate;uniform vec2 uniformOffset;void main(){varyinTexCoordinate=attrVertex+uniformOffset;gl_Position=vec4(attrVertex,0,1);}"), o.compileShader(r); var i = o.createShader(o.FRAGMENT_SHADER); o.shaderSource(i, "precision mediump float;varying vec2 varyinTexCoordinate;void main() {gl_FragColor=vec4(varyinTexCoordinate,0,1);}"), o.compileShader(i), o.attachShader(a, r), o.attachShader(a, i), o.linkProgram(a), o.useProgram(a), a.vertexPosAttrib = o.getAttribLocation(a, "attrVertex"), a.offsetUniform = o.getUniformLocation(a, "uniformOffset"), o.enableVertexAttribArray(a.vertexPosArray), o.vertexAttribPointer(a.vertexPosAttrib, t.itemSize, o.FLOAT, !1, 0, 0), o.uniform2f(a.offsetUniform, 1, 1), o.drawArrays(o.TRIANGLE_STRIP, 0, t.numItems); try { l.push(o.canvas.toDataURL()) } catch (e) { } l.push("extensions:" + (o.getSupportedExtensions() || []).join(";")), l.push("webgl aliased line width range:" + e(o.getParameter(o.ALIASED_LINE_WIDTH_RANGE))), l.push("webgl aliased point size range:" + e(o.getParameter(o.ALIASED_POINT_SIZE_RANGE))), l.push("webgl alpha bits:" + o.getParameter(o.ALPHA_BITS)), l.push("webgl antialiasing:" + (o.getContextAttributes().antialias ? "yes" : "no")), l.push("webgl blue bits:" + o.getParameter(o.BLUE_BITS)), l.push("webgl depth bits:" + o.getParameter(o.DEPTH_BITS)), l.push("webgl green bits:" + o.getParameter(o.GREEN_BITS)), l.push("webgl max anisotropy:" + function (e) { var t = e.getExtension("EXT_texture_filter_anisotropic") || e.getExtension("WEBKIT_EXT_texture_filter_anisotropic") || e.getExtension("MOZ_EXT_texture_filter_anisotropic"); if (t) { var n = e.getParameter(t.MAX_TEXTURE_MAX_ANISOTROPY_EXT); return 0 === n && (n = 2), n } return null }(o)), l.push("webgl max combined texture image units:" + o.getParameter(o.MAX_COMBINED_TEXTURE_IMAGE_UNITS)), l.push("webgl max cube map texture size:" + o.getParameter(o.MAX_CUBE_MAP_TEXTURE_SIZE)), l.push("webgl max fragment uniform vectors:" + o.getParameter(o.MAX_FRAGMENT_UNIFORM_VECTORS)), l.push("webgl max render buffer size:" + o.getParameter(o.MAX_RENDERBUFFER_SIZE)), l.push("webgl max texture image units:" + o.getParameter(o.MAX_TEXTURE_IMAGE_UNITS)), l.push("webgl max texture size:" + o.getParameter(o.MAX_TEXTURE_SIZE)), l.push("webgl max varying vectors:" + o.getParameter(o.MAX_VARYING_VECTORS)), l.push("webgl max vertex attribs:" + o.getParameter(o.MAX_VERTEX_ATTRIBS)), l.push("webgl max vertex texture image units:" + o.getParameter(o.MAX_VERTEX_TEXTURE_IMAGE_UNITS)), l.push("webgl max vertex uniform vectors:" + o.getParameter(o.MAX_VERTEX_UNIFORM_VECTORS)), l.push("webgl max viewport dims:" + e(o.getParameter(o.MAX_VIEWPORT_DIMS))), l.push("webgl red bits:" + o.getParameter(o.RED_BITS)), l.push("webgl renderer:" + o.getParameter(o.RENDERER)), l.push("webgl shading language version:" + o.getParameter(o.SHADING_LANGUAGE_VERSION)), l.push("webgl stencil bits:" + o.getParameter(o.STENCIL_BITS)), l.push("webgl vendor:" + o.getParameter(o.VENDOR)), l.push("webgl version:" + o.getParameter(o.VERSION)); try { var s = o.getExtension("WEBGL_debug_renderer_info"); s && (l.push("webgl unmasked vendor:" + o.getParameter(s.UNMASKED_VENDOR_WEBGL)), l.push("webgl unmasked renderer:" + o.getParameter(s.UNMASKED_RENDERER_WEBGL))) } catch (e) { } return o.getShaderPrecisionFormat && c(["FLOAT", "INT"], function (i) { c(["VERTEX", "FRAGMENT"], function (r) { c(["HIGH", "MEDIUM", "LOW"], function (a) { c(["precision", "rangeMin", "rangeMax"], function (e) { var t = o.getShaderPrecisionFormat(o[r + "_SHADER"], o[a + "_" + i])[e]; "precision" !== e && (e = "precision " + e); var n = ["webgl ", r.toLowerCase(), " shader ", a.toLowerCase(), " ", i.toLowerCase(), " ", e, ":", t].join(""); l.push(n) }) }) }) }), l }, M = function () { try { var e = F(), t = e.getExtension("WEBGL_debug_renderer_info"); return e.getParameter(t.UNMASKED_VENDOR_WEBGL) + "~" + e.getParameter(t.UNMASKED_RENDERER_WEBGL) } catch (e) { return null } }, x = function () { var e = document.createElement("div"); e.innerHTML = "&nbsp;"; var t = !(e.className = "adsbox"); try { document.body.appendChild(e), t = 0 === document.getElementsByClassName("adsbox")[0].offsetHeight, document.body.removeChild(e) } catch (e) { t = !1 } return t }, O = function () { if (void 0 !== navigator.languages) try { if (navigator.languages[0].substr(0, 2) !== navigator.language.substr(0, 2)) return !0 } catch (e) { return !0 } return !1 }, P = function () { return window.screen.width < window.screen.availWidth || window.screen.height < window.screen.availHeight }, b = function () { var e, t = navigator.userAgent.toLowerCase(), n = navigator.oscpu, a = navigator.platform.toLowerCase(); if (e = 0 <= t.indexOf("windows phone") ? "Windows Phone" : 0 <= t.indexOf("win") ? "Windows" : 0 <= t.indexOf("android") ? "Android" : 0 <= t.indexOf("linux") ? "Linux" : 0 <= t.indexOf("iphone") || 0 <= t.indexOf("ipad") ? "iOS" : 0 <= t.indexOf("mac") ? "Mac" : "Other", ("ontouchstart" in window || 0 < navigator.maxTouchPoints || 0 < navigator.msMaxTouchPoints) && "Windows Phone" !== e && "Android" !== e && "iOS" !== e && "Other" !== e) return !0; if (void 0 !== n) { if (0 <= (n = n.toLowerCase()).indexOf("win") && "Windows" !== e && "Windows Phone" !== e) return !0; if (0 <= n.indexOf("linux") && "Linux" !== e && "Android" !== e) return !0; if (0 <= n.indexOf("mac") && "Mac" !== e && "iOS" !== e) return !0; if ((-1 === n.indexOf("win") && -1 === n.indexOf("linux") && -1 === n.indexOf("mac")) != ("Other" === e)) return !0 } return 0 <= a.indexOf("win") && "Windows" !== e && "Windows Phone" !== e || ((0 <= a.indexOf("linux") || 0 <= a.indexOf("android") || 0 <= a.indexOf("pike")) && "Linux" !== e && "Android" !== e || ((0 <= a.indexOf("mac") || 0 <= a.indexOf("ipad") || 0 <= a.indexOf("ipod") || 0 <= a.indexOf("iphone")) && "Mac" !== e && "iOS" !== e || ((-1 === a.indexOf("win") && -1 === a.indexOf("linux") && -1 === a.indexOf("mac")) != ("Other" === e) || void 0 === navigator.plugins && "Windows" !== e && "Windows Phone" !== e))) }, L = function () { var e, t = navigator.userAgent.toLowerCase(), n = navigator.productSub; if (("Chrome" === (e = 0 <= t.indexOf("firefox") ? "Firefox" : 0 <= t.indexOf("opera") || 0 <= t.indexOf("opr") ? "Opera" : 0 <= t.indexOf("chrome") ? "Chrome" : 0 <= t.indexOf("safari") ? "Safari" : 0 <= t.indexOf("trident") ? "Internet Explorer" : "Other") || "Safari" === e || "Opera" === e) && "20030107" !== n) return !0; var a, r = eval.toString().length; if (37 === r && "Safari" !== e && "Firefox" !== e && "Other" !== e) return !0; if (39 === r && "Internet Explorer" !== e && "Other" !== e) return !0; if (33 === r && "Chrome" !== e && "Opera" !== e && "Other" !== e) return !0; try { throw "a" } catch (e) { try { e.toSource(), a = !0 } catch (e) { a = !1 } } return a && "Firefox" !== e && "Other" !== e }, I = function () { var e = document.createElement("canvas"); return !(!e.getContext || !e.getContext("2d")) }, k = function () { if (!I()) return !1; var e = F(); return !!window.WebGLRenderingContext && !!e }, R = function () { return "Microsoft Internet Explorer" === navigator.appName || !("Netscape" !== navigator.appName || !/Trident/.test(navigator.userAgent)) }, D = function () { return void 0 !== window.swfobject }, N = function () { return window.swfobject.hasFlashPlayerVersion("9.0.0") }, _ = function (t, e) { var n = "___fp_swf_loaded"; window[n] = function (e) { t(e) }; var a, r, i = e.fonts.swfContainerId; (r = document.createElement("div")).setAttribute("id", a.fonts.swfContainerId), document.body.appendChild(r); var o = { onReady: n }; window.swfobject.embedSWF(e.fonts.swfPath, i, "1", "1", "9.0.0", !1, o, { allowScriptAccess: "always", menu: "false" }, {}) }, F = function () { var e = document.createElement("canvas"), t = null; try { t = e.getContext("webgl") || e.getContext("experimental-webgl") } catch (e) { } return t || (t = null), t }, G = [{ key: "userAgent", getData: function (e) { e(navigator.userAgent) } }, { key: "language", getData: function (e, t) { e(navigator.language || navigator.userLanguage || navigator.browserLanguage || navigator.systemLanguage || t.NOT_AVAILABLE) } }, { key: "colorDepth", getData: function (e, t) { e(window.screen.colorDepth || t.NOT_AVAILABLE) } }, { key: "deviceMemory", getData: function (e, t) { e(navigator.deviceMemory || t.NOT_AVAILABLE) } }, { key: "pixelRatio", getData: function (e, t) { e(window.devicePixelRatio || t.NOT_AVAILABLE) } }, { key: "hardwareConcurrency", getData: function (e, t) { e(S(t)) } }, { key: "screenResolution", getData: function (e, t) { e(a(t)) } }, { key: "availableScreenResolution", getData: function (e, t) { e(r(t)) } }, { key: "timezoneOffset", getData: function (e) { e((new Date).getTimezoneOffset()) } }, { key: "timezone", getData: function (e, t) { window.Intl && window.Intl.DateTimeFormat ? e((new window.Intl.DateTimeFormat).resolvedOptions().timeZone) : e(t.NOT_AVAILABLE) } }, { key: "sessionStorage", getData: function (e, t) { e(p(t)) } }, { key: "localStorage", getData: function (e, t) { e(v(t)) } }, { key: "indexedDb", getData: function (e, t) { e(A(t)) } }, { key: "addBehavior", getData: function (e) { e(!(!document.body || !document.body.addBehavior)) } }, { key: "openDatabase", getData: function (e) { e(!!window.openDatabase) } }, { key: "cpuClass", getData: function (e, t) { e(C(t)) } }, { key: "platform", getData: function (e, t) { e(B(t)) } }, { key: "doNotTrack", getData: function (e, t) { e(w(t)) } }, { key: "plugins", getData: function (e, t) { R() ? t.plugins.excludeIE ? e(t.EXCLUDED) : e(o(t)) : e(i(t)) } }, { key: "canvas", getData: function (e, t) { I() ? e(y(t)) : e(t.NOT_AVAILABLE) } }, { key: "webgl", getData: function (e, t) { k() ? e(E()) : e(t.NOT_AVAILABLE) } }, { key: "webglVendorAndRenderer", getData: function (e) { k() ? e(M()) : e() } }, { key: "adBlock", getData: function (e) { e(x()) } }, { key: "hasLiedLanguages", getData: function (e) { e(O()) } }, { key: "hasLiedResolution", getData: function (e) { e(P()) } }, { key: "hasLiedOs", getData: function (e) { e(b()) } }, { key: "hasLiedBrowser", getData: function (e) { e(L()) } }, { key: "touchSupport", getData: function (e) { e(t()) } }, { key: "fonts", getData: function (e, t) { var u = ["monospace", "sans-serif", "serif"], d = ["Andale Mono", "Arial", "Arial Black", "Arial Hebrew", "Arial MT", "Arial Narrow", "Arial Rounded MT Bold", "Arial Unicode MS", "Bitstream Vera Sans Mono", "Book Antiqua", "Bookman Old Style", "Calibri", "Cambria", "Cambria Math", "Century", "Century Gothic", "Century Schoolbook", "Comic Sans", "Comic Sans MS", "Consolas", "Courier", "Courier New", "Geneva", "Georgia", "Helvetica", "Helvetica Neue", "Impact", "Lucida Bright", "Lucida Calligraphy", "Lucida Console", "Lucida Fax", "LUCIDA GRANDE", "Lucida Handwriting", "Lucida Sans", "Lucida Sans Typewriter", "Lucida Sans Unicode", "Microsoft Sans Serif", "Monaco", "Monotype Corsiva", "MS Gothic", "MS Outlook", "MS PGothic", "MS Reference Sans Serif", "MS Sans Serif", "MS Serif", "MYRIAD", "MYRIAD PRO", "Palatino", "Palatino Linotype", "Segoe Print", "Segoe Script", "Segoe UI", "Segoe UI Light", "Segoe UI Semibold", "Segoe UI Symbol", "Tahoma", "Times", "Times New Roman", "Times New Roman PS", "Trebuchet MS", "Verdana", "Wingdings", "Wingdings 2", "Wingdings 3"]; t.fonts.extendedJsFonts && (d = d.concat(["Abadi MT Condensed Light", "Academy Engraved LET", "ADOBE CASLON PRO", "Adobe Garamond", "ADOBE GARAMOND PRO", "Agency FB", "Aharoni", "Albertus Extra Bold", "Albertus Medium", "Algerian", "Amazone BT", "American Typewriter", "American Typewriter Condensed", "AmerType Md BT", "Andalus", "Angsana New", "AngsanaUPC", "Antique Olive", "Aparajita", "Apple Chancery", "Apple Color Emoji", "Apple SD Gothic Neo", "Arabic Typesetting", "ARCHER", "ARNO PRO", "Arrus BT", "Aurora Cn BT", "AvantGarde Bk BT", "AvantGarde Md BT", "AVENIR", "Ayuthaya", "Bandy", "Bangla Sangam MN", "Bank Gothic", "BankGothic Md BT", "Baskerville", "Baskerville Old Face", "Batang", "BatangChe", "Bauer Bodoni", "Bauhaus 93", "Bazooka", "Bell MT", "Bembo", "Benguiat Bk BT", "Berlin Sans FB", "Berlin Sans FB Demi", "Bernard MT Condensed", "BernhardFashion BT", "BernhardMod BT", "Big Caslon", "BinnerD", "Blackadder ITC", "BlairMdITC TT", "Bodoni 72", "Bodoni 72 Oldstyle", "Bodoni 72 Smallcaps", "Bodoni MT", "Bodoni MT Black", "Bodoni MT Condensed", "Bodoni MT Poster Compressed", "Bookshelf Symbol 7", "Boulder", "Bradley Hand", "Bradley Hand ITC", "Bremen Bd BT", "Britannic Bold", "Broadway", "Browallia New", "BrowalliaUPC", "Brush Script MT", "Californian FB", "Calisto MT", "Calligrapher", "Candara", "CaslonOpnface BT", "Castellar", "Centaur", "Cezanne", "CG Omega", "CG Times", "Chalkboard", "Chalkboard SE", "Chalkduster", "Charlesworth", "Charter Bd BT", "Charter BT", "Chaucer", "ChelthmITC Bk BT", "Chiller", "Clarendon", "Clarendon Condensed", "CloisterBlack BT", "Cochin", "Colonna MT", "Constantia", "Cooper Black", "Copperplate", "Copperplate Gothic", "Copperplate Gothic Bold", "Copperplate Gothic Light", "CopperplGoth Bd BT", "Corbel", "Cordia New", "CordiaUPC", "Cornerstone", "Coronet", "Cuckoo", "Curlz MT", "DaunPenh", "Dauphin", "David", "DB LCD Temp", "DELICIOUS", "Denmark", "DFKai-SB", "Didot", "DilleniaUPC", "DIN", "DokChampa", "Dotum", "DotumChe", "Ebrima", "Edwardian Script ITC", "Elephant", "English 111 Vivace BT", "Engravers MT", "EngraversGothic BT", "Eras Bold ITC", "Eras Demi ITC", "Eras Light ITC", "Eras Medium ITC", "EucrosiaUPC", "Euphemia", "Euphemia UCAS", "EUROSTILE", "Exotc350 Bd BT", "FangSong", "Felix Titling", "Fixedsys", "FONTIN", "Footlight MT Light", "Forte", "FrankRuehl", "Fransiscan", "Freefrm721 Blk BT", "FreesiaUPC", "Freestyle Script", "French Script MT", "FrnkGothITC Bk BT", "Fruitger", "FRUTIGER", "Futura", "Futura Bk BT", "Futura Lt BT", "Futura Md BT", "Futura ZBlk BT", "FuturaBlack BT", "Gabriola", "Galliard BT", "Gautami", "Geeza Pro", "Geometr231 BT", "Geometr231 Hv BT", "Geometr231 Lt BT", "GeoSlab 703 Lt BT", "GeoSlab 703 XBd BT", "Gigi", "Gill Sans", "Gill Sans MT", "Gill Sans MT Condensed", "Gill Sans MT Ext Condensed Bold", "Gill Sans Ultra Bold", "Gill Sans Ultra Bold Condensed", "Gisha", "Gloucester MT Extra Condensed", "GOTHAM", "GOTHAM BOLD", "Goudy Old Style", "Goudy Stout", "GoudyHandtooled BT", "GoudyOLSt BT", "Gujarati Sangam MN", "Gulim", "GulimChe", "Gungsuh", "GungsuhChe", "Gurmukhi MN", "Haettenschweiler", "Harlow Solid Italic", "Harrington", "Heather", "Heiti SC", "Heiti TC", "HELV", "Herald", "High Tower Text", "Hiragino Kaku Gothic ProN", "Hiragino Mincho ProN", "Hoefler Text", "Humanst 521 Cn BT", "Humanst521 BT", "Humanst521 Lt BT", "Imprint MT Shadow", "Incised901 Bd BT", "Incised901 BT", "Incised901 Lt BT", "INCONSOLATA", "Informal Roman", "Informal011 BT", "INTERSTATE", "IrisUPC", "Iskoola Pota", "JasmineUPC", "Jazz LET", "Jenson", "Jester", "Jokerman", "Juice ITC", "Kabel Bk BT", "Kabel Ult BT", "Kailasa", "KaiTi", "Kalinga", "Kannada Sangam MN", "Kartika", "Kaufmann Bd BT", "Kaufmann BT", "Khmer UI", "KodchiangUPC", "Kokila", "Korinna BT", "Kristen ITC", "Krungthep", "Kunstler Script", "Lao UI", "Latha", "Leelawadee", "Letter Gothic", "Levenim MT", "LilyUPC", "Lithograph", "Lithograph Light", "Long Island", "Lydian BT", "Magneto", "Maiandra GD", "Malayalam Sangam MN", "Malgun Gothic", "Mangal", "Marigold", "Marion", "Marker Felt", "Market", "Marlett", "Matisse ITC", "Matura MT Script Capitals", "Meiryo", "Meiryo UI", "Microsoft Himalaya", "Microsoft JhengHei", "Microsoft New Tai Lue", "Microsoft PhagsPa", "Microsoft Tai Le", "Microsoft Uighur", "Microsoft YaHei", "Microsoft Yi Baiti", "MingLiU", "MingLiU_HKSCS", "MingLiU_HKSCS-ExtB", "MingLiU-ExtB", "Minion", "Minion Pro", "Miriam", "Miriam Fixed", "Mistral", "Modern", "Modern No. 20", "Mona Lisa Solid ITC TT", "Mongolian Baiti", "MONO", "MoolBoran", "Mrs Eaves", "MS LineDraw", "MS Mincho", "MS PMincho", "MS Reference Specialty", "MS UI Gothic", "MT Extra", "MUSEO", "MV Boli", "Nadeem", "Narkisim", "NEVIS", "News Gothic", "News GothicMT", "NewsGoth BT", "Niagara Engraved", "Niagara Solid", "Noteworthy", "NSimSun", "Nyala", "OCR A Extended", "Old Century", "Old English Text MT", "Onyx", "Onyx BT", "OPTIMA", "Oriya Sangam MN", "OSAKA", "OzHandicraft BT", "Palace Script MT", "Papyrus", "Parchment", "Party LET", "Pegasus", "Perpetua", "Perpetua Titling MT", "PetitaBold", "Pickwick", "Plantagenet Cherokee", "Playbill", "PMingLiU", "PMingLiU-ExtB", "Poor Richard", "Poster", "PosterBodoni BT", "PRINCETOWN LET", "Pristina", "PTBarnum BT", "Pythagoras", "Raavi", "Rage Italic", "Ravie", "Ribbon131 Bd BT", "Rockwell", "Rockwell Condensed", "Rockwell Extra Bold", "Rod", "Roman", "Sakkal Majalla", "Santa Fe LET", "Savoye LET", "Sceptre", "Script", "Script MT Bold", "SCRIPTINA", "Serifa", "Serifa BT", "Serifa Th BT", "ShelleyVolante BT", "Sherwood", "Shonar Bangla", "Showcard Gothic", "Shruti", "Signboard", "SILKSCREEN", "SimHei", "Simplified Arabic", "Simplified Arabic Fixed", "SimSun", "SimSun-ExtB", "Sinhala Sangam MN", "Sketch Rockwell", "Skia", "Small Fonts", "Snap ITC", "Snell Roundhand", "Socket", "Souvenir Lt BT", "Staccato222 BT", "Steamer", "Stencil", "Storybook", "Styllo", "Subway", "Swis721 BlkEx BT", "Swiss911 XCm BT", "Sylfaen", "Synchro LET", "System", "Tamil Sangam MN", "Technical", "Teletype", "Telugu Sangam MN", "Tempus Sans ITC", "Terminal", "Thonburi", "Traditional Arabic", "Trajan", "TRAJAN PRO", "Tristan", "Tubular", "Tunga", "Tw Cen MT", "Tw Cen MT Condensed", "Tw Cen MT Condensed Extra Bold", "TypoUpright BT", "Unicorn", "Univers", "Univers CE 55 Medium", "Univers Condensed", "Utsaah", "Vagabond", "Vani", "Vijaya", "Viner Hand ITC", "VisualUI", "Vivaldi", "Vladimir Script", "Vrinda", "Westminster", "WHITNEY", "Wide Latin", "ZapfEllipt BT", "ZapfHumnst BT", "ZapfHumnst Dm BT", "Zapfino", "Zurich BlkEx BT", "Zurich Ex BT", "ZWAdobeF"])); d = (d = d.concat(t.fonts.userDefinedFonts)).filter(function (e, t) { return d.indexOf(e) === t }); var n = document.getElementsByTagName("body")[0], r = document.createElement("div"), g = document.createElement("div"), a = {}, i = {}, f = function () { var e = document.createElement("span"); return e.style.position = "absolute", e.style.left = "-9999px", e.style.fontSize = "72px", e.style.fontStyle = "normal", e.style.fontWeight = "normal", e.style.letterSpacing = "normal", e.style.lineBreak = "auto", e.style.lineHeight = "normal", e.style.textTransform = "none", e.style.textAlign = "left", e.style.textDecoration = "none", e.style.textShadow = "none", e.style.whiteSpace = "normal", e.style.wordBreak = "normal", e.style.wordSpacing = "normal", e.innerHTML = "mmmmmmmmmmlli", e }, o = function (e) { for (var t = !1, n = 0; n < u.length; n++)if (t = e[n].offsetWidth !== a[u[n]] || e[n].offsetHeight !== i[u[n]]) return t; return t }, l = function () { for (var e = [], t = 0, n = u.length; t < n; t++) { var a = f(); a.style.fontFamily = u[t], r.appendChild(a), e.push(a) } return e }(); n.appendChild(r); for (var s = 0, c = u.length; s < c; s++)a[u[s]] = l[s].offsetWidth, i[u[s]] = l[s].offsetHeight; var h = function () { for (var e, t, n, a = {}, r = 0, i = d.length; r < i; r++) { for (var o = [], l = 0, s = u.length; l < s; l++) { var c = (e = d[r], t = u[l], n = void 0, (n = f()).style.fontFamily = "'" + e + "'," + t, n); g.appendChild(c), o.push(c) } a[d[r]] = o } return a }(); n.appendChild(g); for (var m = [], T = 0, p = d.length; T < p; T++)o(h[d[T]]) && m.push(d[T]); n.removeChild(g), n.removeChild(r), e(m) }, pauseBefore: !0 }, { key: "fontsFlash", getData: function (t, e) { return D() ? N() ? e.fonts.swfPath ? void _(function (e) { t(e) }, e) : t("missing options.fonts.swfPath") : t("flash not installed") : t("swf object not loaded") }, pauseBefore: !0 }, { key: "audio", getData: function (n, e) { var t = e.audio; if (t.excludeIOS11 && navigator.userAgent.match(/OS 11.+Version\/11.+Safari/)) return n(e.EXCLUDED); var a = window.OfflineAudioContext || window.webkitOfflineAudioContext; if (null == a) return n(e.NOT_AVAILABLE); var r = new a(1, 44100, 44100), i = r.createOscillator(); i.type = "triangle", i.frequency.setValueAtTime(1e4, r.currentTime); var o = r.createDynamicsCompressor(); c([["threshold", -50], ["knee", 40], ["ratio", 12], ["reduction", -20], ["attack", 0], ["release", .25]], function (e) { void 0 !== o[e[0]] && "function" == typeof o[e[0]].setValueAtTime && o[e[0]].setValueAtTime(e[1], r.currentTime) }), i.connect(o), o.connect(r.destination), i.start(0), r.startRendering(); var l = setTimeout(function () { return console.warn('Audio fingerprint timed out. Please report bug at https://github.com/Valve/fingerprintjs2 with your user agent: "' + navigator.userAgent + '".'), r.oncomplete = function () { }, r = null, n("audioTimeout") }, t.timeout); r.oncomplete = function (e) { var t; try { clearTimeout(l), t = e.renderedBuffer.getChannelData(0).slice(4500, 5e3).reduce(function (e, t) { return e + Math.abs(t) }, 0).toString(), i.disconnect(), o.disconnect() } catch (e) { return void n(e) } n(t) } } }, { key: "enumerateDevices", getData: function (t, e) { if (!n()) return t(e.NOT_AVAILABLE); navigator.mediaDevices.enumerateDevices().then(function (e) { t(e.map(function (e) { return "id=" + e.deviceId + ";gid=" + e.groupId + ";" + e.kind + ";" + e.label })) }).catch(function (e) { t(e) }) } }], U = function (e) { throw new Error("'new Fingerprint()' is deprecated, see https://github.com/Valve/fingerprintjs2#upgrade-guide-from-182-to-200") }; return U.get = function (n, a) { a ? n || (n = {}) : (a = n, n = {}), function (e, t) { if (null == t) return; var n, a; for (a in t) null == (n = t[a]) || Object.prototype.hasOwnProperty.call(e, a) || (e[a] = n) }(n, e), n.components = n.extraComponents.concat(G); var r = { data: [], addPreprocessedComponent: function (e, t) { "function" == typeof n.preprocessor && (t = n.preprocessor(e, t)), r.data.push({ key: e, value: t }) } }, i = -1, o = function (e) { if ((i += 1) >= n.components.length) a(r.data); else { var t = n.components[i]; if (n.excludes[t.key]) o(!1); else { if (!e && t.pauseBefore) return i -= 1, void setTimeout(function () { o(!0) }, 1); try { t.getData(function (e) { r.addPreprocessedComponent(t.key, e), o(!1) }, n) } catch (e) { r.addPreprocessedComponent(t.key, String(e)), o(!1) } } } }; o(!1) }, U.getPromise = function (n) { return new Promise(function (e, t) { U.get(n, e) }) }, U.getV18 = function (i, o) { return null == o && (o = i, i = {}), U.get(i, function (e) { for (var t = [], n = 0; n < e.length; n++) { var a = e[n]; if (a.value === (i.NOT_AVAILABLE || "not available")) t.push({ key: a.key, value: "unknown" }); else if ("plugins" === a.key) t.push({ key: "plugins", value: s(a.value, function (e) { var t = s(e[2], function (e) { return e.join ? e.join("~") : e }).join(","); return [e[0], e[1], t].join("::") }) }); else if (-1 !== ["canvas", "webgl"].indexOf(a.key)) t.push({ key: a.key, value: a.value.join("~") }); else if (-1 !== ["sessionStorage", "localStorage", "indexedDb", "addBehavior", "openDatabase"].indexOf(a.key)) { if (!a.value) continue; t.push({ key: a.key, value: 1 }) } else a.value ? t.push(a.value.join ? { key: a.key, value: a.value.join(";") } : a) : t.push({ key: a.key, value: a.value }) } var r = l(s(t, function (e) { return e.value }).join("~~~"), 31); o(r, t) }) }, U.x64hash128 = l, U.VERSION = "2.0.0", U });

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
    let userStartTime = getCurrentTimeInGMT7();
    let totalTimeOnsite = 0;
    let startTime = getCurrentTimeInGMT7();

    function getDeviceFingerprint(callback) {
        Fingerprint2.get(function (components) {
            const values = components.map(function (component) { return component.value; });
            const fingerprint = Fingerprint2.x64hash128(values.join(''), 31);
            callback(fingerprint);
        });
    }

    async function checkDeviceExclusion() {
        const userInfo = getUserInfo();
        return new Promise((resolve) => {
            getDeviceFingerprint(async (fingerprint) => {
                const deviceData = {
                    user_agent: userInfo.userAgent,
                    platform: userInfo.platform,
                    language: userInfo.language,
                    cookies_enabled: userInfo.cookiesEnabled,
                    screen_width: userInfo.screenWidth,
                    screen_height: userInfo.screenHeight,
                    timezone: userInfo.timezone,
                    fingerprint: fingerprint
                };

                try {
                    const response = await fetch(`${checkURL()}/api/check-device`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(deviceData)
                    });
                    const data = await response.json();
                    isExcluded = data.is_excluded === true;
                } catch (error) {
                    console.error('Error checking device:', error);
                    isExcluded = false;
                }
                resolve();
            });
        });
    }

    checkDeviceExclusion().then(() => {
        if (!isExcluded) startHeartbeat();
    });

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

        const botDetected = botPatterns.some(pattern => pattern.test(userAgent)) || isSuspicious;
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
                sendDataToServer(videoData, '/api/create-video-timeline');
            }

            if (aiTrainingData.length > 0) {
                const trainingData = {
                    uuid: userUUID,
                    domain: window.location.hostname,
                    sessionStart: userStartTime,
                    sessionEnd: getCurrentTimeInGMT7(),
                    events: aiTrainingData
                };
                sendDataToServer(trainingData, '/api/collect-ai-training-data');
            }
        }
    }

    function updateTimeOnsite() {
        const currentTime = getCurrentTimeInGMT7();
        totalTimeOnsite += currentTime - startTime;
        startTime = currentTime;
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
            if (!referrer || referrer === '') {
                return 'Direct';
            }
            const url = new URL(referrer);
            const hostname = url.hostname.toLowerCase();

            if (hostname.includes('google.')) {
                return 'Google Search';
            } else if (hostname.includes('facebook.com')) {
                return 'Facebook';
            } else if (hostname.includes('twitter.com') || hostname.includes('x.com')) {
                return 'Twitter';
            } else if (hostname.includes('instagram.com')) {
                return 'Instagram';
            } else if (hostname.includes('youtube.com')) {
                return 'YouTube';
            } else if (hostname.includes('bing.com')) {
                return 'Bing Search';
            } else if (hostname.includes('yahoo.com')) {
                return 'Yahoo Search';
            } else if (hostname.includes('tiktok.com')) {
                return 'TikTok';
            } else {
                return 'Other';
            }
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
            trafficSource: getTrafficSource(document.referrer),
            pageLoadTime: performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart
        };

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
            battery: batteryInfo
        };
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
})();