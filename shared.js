/* ============================================
   COMMAND CENTER — Shared Navigation Component
   ============================================ */

const API_BASE = 'https://localhost:8000/api';
const HOSPITAL_PROFILE_KEY = 'smartHospitalProfile';
const ADMIN_SESSION_KEY = 'smartHospitalAdminSession';
const DEFAULT_HOSPITAL_NAME = 'COMMAND CENTER';
const ADMIN_LOGIN_PAGE = 'code.html';
const ADMIN_DEFAULT_REDIRECT = 'index.html';
const PUBLIC_DASHBOARD_PAGE = 'index.html';

/**
 * Builds the sidebar and topbar for every page.
 * Call buildNavigation('pageName') where pageName matches a key in NAV_ITEMS.
 */

const NAV_ITEMS = [
    { key: 'dashboard',     label: 'Dashboard',     icon: 'dashboard',     href: 'index.html' },
    { key: 'add-patient',   label: 'Add Patient',   icon: 'person_add',    href: 'add-patient.html' },
    { key: 'patient-list',  label: 'Monitoring',    icon: 'monitor_heart', href: 'patient-list.html' },
    { key: 'ai-analysis',   label: 'AI Analysis',   icon: 'psychology',    href: 'ai-analysis.html' },
    { key: 'resources',     label: 'Resources',     icon: 'account_tree',  href: 'resources.html' },
    { key: 'admin',         label: 'Admin Panel',   icon: 'admin_panel_settings', href: 'admin-dashboard.html' },
    { key: 'bias-checker',  label: 'Bias Checker',  icon: 'balance',       href: 'bias-checker.html' },
    { key: 'reports',       label: 'Reports',        icon: 'assessment',   href: 'reports.html' },
];

const FOOTER_ITEMS = [
    { label: 'Settings', icon: 'settings', href: '#' },
    { label: 'Support',  icon: 'help',     href: '#' },
    { label: 'Sign Out', icon: 'logout',   href: '#', authOnly: true, action: 'logout' },
];

function getVisibleNavItems() {
    if (hasAdminSession()) {
        return NAV_ITEMS;
    }

    return NAV_ITEMS.filter((item) => item.key === 'dashboard');
}

function getVisibleFooterItems() {
    if (hasAdminSession()) {
        return FOOTER_ITEMS;
    }

    return FOOTER_ITEMS.filter((item) => !item.authOnly && item.label === 'Support');
}

function buildSidebar(activePage) {
    const sidebar = document.createElement('aside');
    sidebar.className = 'sidebar';
    sidebar.id = 'main-sidebar';
    const emergencyButton = hasAdminSession()
        ? `<button class="sidebar-emergency" onclick="alert('Emergency override activated.')">Emergency Override</button>`
        : `<button class="sidebar-emergency" style="opacity:0.6;cursor:pointer;" onclick="redirectToAdminLogin(window.location.href)">Admin Login Required</button>`;

    // Header
    sidebar.innerHTML = `
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <span class="material-symbols-outlined">health_and_safety</span>
                </div>
                <div>
                    <div class="sidebar-brand-title" data-hospital-name>${escapeHtml(getHospitalName())}</div>
                    <div class="sidebar-brand-subtitle" data-hospital-meta>${escapeHtml(getHospitalMetaLine())}</div>
                </div>
            </div>
        </div>
        ${emergencyButton}
        <nav class="sidebar-nav" id="sidebar-nav"></nav>
        <div class="sidebar-footer" id="sidebar-footer"></div>
    `;

    // Navigation links
    const nav = sidebar.querySelector('#sidebar-nav');
    getVisibleNavItems().forEach(item => {
        const a = document.createElement('a');
        a.href = item.href;
        a.className = 'nav-link' + (item.key === activePage ? ' active' : '');
        a.id = 'nav-' + item.key;
        a.innerHTML = `
            <span class="material-symbols-outlined">${item.icon}</span>
            <span>${item.label}</span>
        `;
        nav.appendChild(a);
    });

    // Footer links
    const footer = sidebar.querySelector('#sidebar-footer');
    getVisibleFooterItems().forEach(item => {
        const a = document.createElement('a');
        a.href = item.href;
        a.className = 'nav-link';
        if (item.action) {
            a.dataset.action = item.action;
        }
        a.innerHTML = `
            <span class="material-symbols-outlined">${item.icon}</span>
            <span>${item.label}</span>
        `;
        if (item.action === 'logout') {
            a.addEventListener('click', async (event) => {
                event.preventDefault();
                await logoutAdmin();
            });
        }
        footer.appendChild(a);
    });

    return sidebar;
}

function buildTopbar() {
    const header = document.createElement('header');
    header.className = 'topbar';
    header.id = 'main-topbar';
    const adminSession = getAdminSession();
    const accountTitle = adminSession?.email
        ? `Signed in as ${adminSession.email}. Open hospital profile.`
        : 'Admin login';
    const adminDotStyle = adminSession?.isLoggedIn ? '' : 'display:none;';
    const searchPlaceholder = hasAdminSession()
        ? 'Search patients, wards, or resources...'
        : 'Sign in to search patient and hospital records';
    const searchDisabled = hasAdminSession() ? '' : 'disabled';

    header.innerHTML = `
        <div class="topbar-brand-wrap">
            <div class="topbar-brand" data-hospital-name>${escapeHtml(getHospitalName())}</div>
            <div class="topbar-meta" data-hospital-meta>${escapeHtml(getHospitalMetaLine())}</div>
        </div>
        <div class="topbar-actions">
            <div class="topbar-search">
                <span class="material-symbols-outlined">search</span>
                <input type="text" placeholder="${escapeHtml(searchPlaceholder)}" ${searchDisabled} />
            </div>
            <button class="icon-btn" title="Notifications">
                <span class="material-symbols-outlined">notifications</span>
                <span class="notification-dot"></span>
            </button>
            <button class="icon-btn" title="Emergency">
                <span class="material-symbols-outlined">emergency_home</span>
            </button>
            <button class="icon-btn" id="topbar-account-btn" title="${escapeHtml(accountTitle)}">
                <span class="material-symbols-outlined">account_circle</span>
                <span class="notification-dot" data-admin-state-dot style="${adminDotStyle}"></span>
            </button>
        </div>
    `;

    return header;
}

function buildNavigation(activePage) {
    if (!document.querySelector('.cyber-grid')) {
        const grid = document.createElement('div');
        grid.className = 'cyber-grid';
        document.body.prepend(grid);
    }

    if (!document.getElementById('main-sidebar')) {
        document.body.prepend(buildSidebar(activePage));
    }

    const mainContent = document.querySelector('.main-content');
    if (mainContent && !document.getElementById('main-topbar')) {
        mainContent.prepend(buildTopbar());
    }

    syncHospitalBranding();
    syncAdminUi();
}

if (typeof window !== 'undefined') {
    window.API_BASE = API_BASE;
    window.buildNavigation = buildNavigation;
    window.getHospitalProfile = getHospitalProfile;
    window.getHospitalName = getHospitalName;
    window.saveHospitalProfile = saveHospitalProfile;
    window.clearHospitalProfile = clearHospitalProfile;
    window.getAdminSession = getAdminSession;
    window.hasAdminSession = hasAdminSession;
    window.saveAdminSession = saveAdminSession;
    window.clearAdminSession = clearAdminSession;
    window.getAdminToken = getAdminToken;
    window.enforceAdminAccess = enforceAdminAccess;
    window.redirectToAdminLogin = redirectToAdminLogin;
    window.getPostLoginRedirect = getPostLoginRedirect;
    window.logoutAdmin = logoutAdmin;
    window.syncHospitalBranding = syncHospitalBranding;
    window.addEventListener('storage', (event) => {
        if (event.key === HOSPITAL_PROFILE_KEY) {
            syncHospitalBranding();
        }
        if (event.key === ADMIN_SESSION_KEY) {
            syncHospitalBranding();
            syncAdminUi();
        }
    });
}

// ================= UTILITY FUNCTIONS =================

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[char]));
}

function safeStorageGet(key) {
    if (typeof window === 'undefined' || typeof window.localStorage === 'undefined') {
        return null;
    }

    try {
        return window.localStorage.getItem(key);
    } catch (err) {
        console.warn('Unable to read localStorage key', key, err);
        return null;
    }
}

function safeStorageSet(key, value) {
    if (typeof window === 'undefined' || typeof window.localStorage === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem(key, value);
    } catch (err) {
        console.warn('Unable to write localStorage key', key, err);
    }
}

function safeStorageRemove(key) {
    if (typeof window === 'undefined' || typeof window.localStorage === 'undefined') {
        return;
    }

    try {
        window.localStorage.removeItem(key);
    } catch (err) {
        console.warn('Unable to remove localStorage key', key, err);
    }
}

function getHospitalProfile() {
    const raw = safeStorageGet(HOSPITAL_PROFILE_KEY);
    if (!raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (err) {
        console.warn('Unable to parse hospital profile', err);
        return null;
    }
}

function getHospitalName() {
    const profile = getHospitalProfile();
    const name = profile?.hospital_name || profile?.hospitalName;
    return String(name || DEFAULT_HOSPITAL_NAME).trim() || DEFAULT_HOSPITAL_NAME;
}

function getHospitalMetaLine() {
    const profile = getHospitalProfile();
    if (!profile) {
        return 'Hospital command center online';
    }

    const parts = [];
    if (profile.doctor_name) {
        parts.push(`Lead: ${profile.doctor_name}`);
    }
    if (profile.hospital_city) {
        parts.push(profile.hospital_city);
    }
    if (profile.doctor_role) {
        parts.push(profile.doctor_role);
    }

    return parts.join(' | ') || 'Hospital command center online';
}

function saveHospitalProfile(profile) {
    const existing = getHospitalProfile() || {};
    const normalized = {
        ...existing,
        ...profile,
        hospital_name: String(profile?.hospital_name || existing.hospital_name || '').trim(),
        doctor_name: String(profile?.doctor_name || existing.doctor_name || '').trim(),
        doctor_role: String(profile?.doctor_role || existing.doctor_role || '').trim(),
        doctor_email: String(profile?.doctor_email || existing.doctor_email || '').trim(),
        hospital_city: String(profile?.hospital_city || existing.hospital_city || '').trim(),
        total_beds: String(profile?.total_beds || existing.total_beds || '').trim(),
        emergency_contact: String(profile?.emergency_contact || existing.emergency_contact || '').trim(),
        updated_at: new Date().toISOString(),
    };

    safeStorageSet(HOSPITAL_PROFILE_KEY, JSON.stringify(normalized));
    syncHospitalBranding();
    return normalized;
}

function clearHospitalProfile() {
    safeStorageRemove(HOSPITAL_PROFILE_KEY);
    syncHospitalBranding();
}

function getAdminSession() {
    const raw = safeStorageGet(ADMIN_SESSION_KEY);
    if (!raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (err) {
        console.warn('Unable to parse admin session', err);
        return null;
    }
}

function hasAdminSession() {
    const session = getAdminSession();
    return Boolean(session?.isLoggedIn && session?.token);
}

function getAdminToken() {
    const session = getAdminSession();
    return String(session?.token || '').trim();
}

function saveAdminSession(session) {
    const current = getAdminSession() || {};
    const normalized = {
        ...current,
        ...session,
        isLoggedIn: true,
        email: String(session?.email || current.email || '').trim(),
        display_name: String(session?.display_name || current.display_name || '').trim(),
        token: String(session?.token || current.token || '').trim(),
        expires_at: String(session?.expires_at || current.expires_at || '').trim(),
        updated_at: new Date().toISOString(),
    };

    safeStorageSet(ADMIN_SESSION_KEY, JSON.stringify(normalized));
    syncAdminUi();
    return normalized;
}

function clearAdminSession() {
    safeStorageRemove(ADMIN_SESSION_KEY);
    syncAdminUi();
}

function getCurrentPageName() {
    if (typeof window === 'undefined') {
        return '';
    }

    const path = window.location.pathname || '';
    const segments = path.split('/');
    return (segments.pop() || '').toLowerCase();
}

function isPublicDashboardPage() {
    const pageName = getCurrentPageName();
    return pageName === '' || pageName === PUBLIC_DASHBOARD_PAGE;
}

function isAdminLoginPage() {
    return getCurrentPageName() === ADMIN_LOGIN_PAGE;
}

function getPostLoginRedirect() {
    if (typeof window === 'undefined') {
        return ADMIN_DEFAULT_REDIRECT;
    }

    const params = new URLSearchParams(window.location.search);
    return params.get('next') || ADMIN_DEFAULT_REDIRECT;
}

function getAdminLoginUrl(nextUrl) {
    if (typeof window === 'undefined') {
        return ADMIN_LOGIN_PAGE;
    }

    const loginUrl = new URL(ADMIN_LOGIN_PAGE, window.location.href);
    const targetUrl = String(nextUrl || window.location.href || '').trim();

    if (targetUrl) {
        loginUrl.searchParams.set('next', targetUrl);
    }

    return loginUrl.toString();
}

function redirectToAdminLogin(nextUrl) {
    if (typeof window === 'undefined') {
        return;
    }

    window.location.href = getAdminLoginUrl(nextUrl);
}

function enforceAdminAccess() {
    if (hasAdminSession()) {
        return true;
    }

    if (typeof document !== 'undefined' && document.body) {
        document.body.style.visibility = 'hidden';
    }

    redirectToAdminLogin(window.location.href);
    return false;
}

function handleUnauthorizedResponse() {
    clearAdminSession();

    if (isAdminLoginPage()) {
        return;
    }

    if (isPublicDashboardPage()) {
        window.location.href = ADMIN_DEFAULT_REDIRECT;
        return;
    }

    redirectToAdminLogin(window.location.href);
}

function buildRequestHeaders(headers = {}, body = null) {
    const mergedHeaders = { ...headers };
    const isFormData = typeof FormData !== 'undefined' && body instanceof FormData;
    const hasContentType = Object.keys(mergedHeaders).some((key) => key.toLowerCase() === 'content-type');
    const hasAccept = Object.keys(mergedHeaders).some((key) => key.toLowerCase() === 'accept');

    if (!isFormData && !hasContentType) {
        mergedHeaders['Content-Type'] = 'application/json';
    }

    if (!hasAccept) {
        mergedHeaders.Accept = 'application/json';
    }

    const token = getAdminToken();
    if (token && !Object.keys(mergedHeaders).some((key) => key.toLowerCase() === 'x-admin-token')) {
        mergedHeaders['X-Admin-Token'] = token;
    }

    return mergedHeaders;
}

function syncAdminUi() {
    if (typeof document === 'undefined') {
        return;
    }

    const session = getAdminSession();
    const button = document.getElementById('topbar-account-btn');
    if (!button) {
        return;
    }

    button.title = session?.email
        ? `Signed in as ${session.email}. Open hospital profile.`
        : 'Admin login';

    const dot = button.querySelector('[data-admin-state-dot]');
    if (dot) {
        dot.style.display = session?.isLoggedIn ? '' : 'none';
    }

    const searchInput = document.querySelector('.topbar-search input');
    if (searchInput) {
        searchInput.disabled = !hasAdminSession();
        searchInput.placeholder = hasAdminSession()
            ? 'Search patients, wards, or resources...'
            : 'Sign in to search patient and hospital records';
    }

    button.onclick = async () => {
        if (hasAdminSession()) {
            if (getCurrentPageName() !== 'admin-dashboard.html') {
                window.location.href = 'admin-dashboard.html';
                return;
            }

            const profileForm = document.getElementById('hospital-registration-form');
            if (profileForm) {
                profileForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }

        redirectToAdminLogin(window.location.href);
    };
}

async function logoutAdmin() {
    const token = getAdminToken();

    try {
        if (token) {
            await fetch(`${API_BASE}/admin_logout.php`, {
                method: 'POST',
                headers: buildRequestHeaders(),
            });
        }
    } catch (err) {
        console.warn('Unable to notify logout endpoint', err);
    } finally {
        clearAdminSession();
        window.location.href = ADMIN_DEFAULT_REDIRECT;
    }
}

function syncHospitalBranding() {
    const hospitalName = getHospitalName();
    const metaLine = getHospitalMetaLine();

    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;
    if (!root.dataset.baseTitle) {
        root.dataset.baseTitle = document.title;
    }

    document.title = root.dataset.baseTitle.replace(/Vigilant Neon|VIGILANT NEON|Command Center|COMMAND CENTER/gi, hospitalName);

    document.querySelectorAll('[data-hospital-name]').forEach((node) => {
        node.textContent = hospitalName;
    });

    document.querySelectorAll('[data-hospital-meta]').forEach((node) => {
        node.textContent = metaLine;
    });
}

function formatAiExplanationHtml(text) {
    const normalized = String(text ?? '').replace(/\r\n?/g, '\n').trim();
    if (!normalized) {
        return '<span style="color:var(--on-surface-variant);">No AI explanation available.</span>';
    }

    const highlighted = escapeHtml(normalized)
        .replace(/^(Reasoning:)/im, '<strong style="color:var(--primary-container);">$1</strong>')
        .replace(/^(Risk Level:)/im, '<strong style="color:var(--secondary);">$1</strong>')
        .replace(/^(Conclusion:)/im, '<strong style="color:var(--tertiary-container);">$1</strong>');

    return highlighted
        .replace(/\n{2,}/g, '<br><br>')
        .replace(/\n/g, '<br>');
}

if (typeof window !== 'undefined') {
    window.formatAiExplanationHtml = formatAiExplanationHtml;
}

async function analyzePatient(patientData) {
    try {
        console.log('AI request payload:', patientData);

        const res = await fetch(`${API_BASE}/analyze_patient.php`, {
            method: 'POST',
            headers: buildRequestHeaders(),
            body: JSON.stringify(patientData),
        });

        if (!res.ok) {
            if (res.status === 401) {
                handleUnauthorizedResponse();
            }
            throw new Error(`Server error (${res.status})`);
        }

        const data = await res.json();
        console.log('AI Response:', data);

        if (!data || typeof data !== 'object' || typeof data.explanation !== 'string') {
            throw new Error('Invalid AI response payload');
        }

        return data;
    } catch (err) {
        console.error('analyzePatient failed:', err);
        return null;
    }
}

if (typeof window !== 'undefined') {
    window.analyzePatient = analyzePatient;
}

function notifyAllocationUpdate() {
    if (typeof window !== 'undefined' && typeof window.localStorage !== 'undefined') {
        window.localStorage.setItem('smartHospitalAllocationsUpdatedAt', String(Date.now()));
    }
}

if (typeof window !== 'undefined') {
    window.notifyAllocationUpdate = notifyAllocationUpdate;
}

/** Fetch wrapper with error handling */
async function apiFetch(endpoint, options = {}) {
    try {
        const url = `${API_BASE}/${endpoint}`;
        const headers = buildRequestHeaders(options.headers || {}, options.body);
        const res = await fetch(url, {
            ...options,
            headers,
        });
        const raw = await res.text();

        let data = null;
        try {
            data = raw ? JSON.parse(raw) : null;
        } catch (parseErr) {
            console.error('Invalid JSON response from', url, parseErr, raw);
            return null;
        }

        if (res.status === 401) {
            handleUnauthorizedResponse();
        }

        if (!res.ok || data?.status === 'error') {
            console.error('API Error:', data?.message || `HTTP ${res.status}`, data);
            return data;
        }

        return data;
    } catch (err) {
        console.error('Network error:', err);
        return null;
    }
}

/** Format number with commas */
function formatNum(n) {
    return Number(n).toLocaleString();
}

/** Show a toast notification */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <span class="material-symbols-outlined">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info'}</span>
        <span>${escapeHtml(message)}</span>
    `;
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ================= PATIENT FORM SUBMIT =================

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("add-patient-form");

    if (form) {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span> Processing...';
            submitBtn.disabled = true;

            const data = {
                name: document.getElementById("patient_name").value,
                age: document.getElementById("patient_age").value,
                gender: document.getElementById("patient_gender").value,
                symptoms: document.getElementById("patient_symptoms").value,
                oxygen_level: document.getElementById("patient_spo2").value
            };

            const result = await apiFetch('add_patient.php', {
                method: 'POST',
                body: JSON.stringify(data),
            });

            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;

            if (result && result.status === 'success') {
                showToast(`Patient "${data.name}" added! Code: ${result.patient_code}`, 'success');
                showToast(result.resource_message || 'Resource assigned automatically based on patient condition.', 'info');
                showToast(`AI Severity: ${result.ai_analysis.severity_score}/10 (${result.ai_analysis.severity})`, 'info');
                form.reset();

                // Show AI analysis panel
                showAiResultPanel(result.ai_analysis, data.name);
                notifyAllocationUpdate();
            } else {
                showToast(result?.message || 'Failed to add patient', 'error');
            }
        });

        const btnAnalyzeAi = document.getElementById("btn-analyze-ai");
        if (btnAnalyzeAi) {
            btnAnalyzeAi.addEventListener("click", async function () {
                const data = {
                    name: document.getElementById("patient_name").value || "Unknown Patient",
                    age: document.getElementById("patient_age").value,
                    gender: document.getElementById("patient_gender").value,
                    symptoms: document.getElementById("patient_symptoms").value,
                    oxygen_level: document.getElementById("patient_spo2").value
                };

                const originalText = btnAnalyzeAi.innerHTML;
                btnAnalyzeAi.innerHTML = '<span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span> Querying Gemini...';
                btnAnalyzeAi.disabled = true;

                const result = await analyzePatient(data);

                btnAnalyzeAi.innerHTML = originalText;
                btnAnalyzeAi.disabled = false;

                if (result && result.status === 'success') {
                    showToast(`Gemini AI Analysis complete`, 'info');
                    showAiResultPanel(result, data.name + ' (Preview)');
                } else {
                    showToast(result?.message || 'Failed to connect to AI', 'error');
                }
            });
        }
    }
});

/** Show AI result panel after patient add */
function showAiResultPanel(ai, patientName) {
    // Remove existing panel
    const existing = document.getElementById('ai-result-panel');
    if (existing) existing.remove();

    const safePatientName = escapeHtml(patientName || 'Unknown Patient');
    const structuredExplanation = typeof ai?.explanation === 'string' ? ai.explanation.trim() : '';
    const rationaleMarkers = Array.isArray(ai?.rationale?.markers) ? ai.rationale.markers : [];
    const hasMetrics = ai?.severity !== undefined || ai?.severity_score !== undefined || ai?.confidence !== undefined || ai?.recommended_resource !== undefined;

    const panel = document.createElement('div');
    panel.id = 'ai-result-panel';
    panel.className = 'glass-panel rounded-xl p-md animate-fade-in';
    panel.style.cssText = 'margin-top: 24px; border-color: rgba(0,242,255,0.3);';

    if (structuredExplanation && rationaleMarkers.length === 0 && !hasMetrics) {
        panel.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 class="h3 flex items-center gap-sm">
                    <span class="material-symbols-outlined" style="color:var(--primary-container);">psychology</span>
                    AI Explanation: ${safePatientName}
                </h3>
                <button onclick="this.closest('#ai-result-panel').remove()" class="icon-btn" title="Close">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div style="padding:20px;background:rgba(19,19,21,0.5);border-radius:var(--radius-md);border:1px solid rgba(255,255,255,0.05);color:var(--on-surface-variant);line-height:1.8;white-space:normal;overflow-wrap:anywhere;word-break:break-word;">
                ${formatAiExplanationHtml(structuredExplanation)}
            </div>
        `;

        const formCard = document.querySelector('.form-card');
        if (formCard) formCard.parentNode.insertBefore(panel, formCard.nextSibling);
        return;
    }

    let rationaleHTML = '';
    if (rationaleMarkers.length > 0) {
        rationaleMarkers.forEach(m => {
            rationaleHTML += `
                <div class="rationale-item" style="display:flex;gap:12px;align-items:flex-start;margin-bottom:16px;">
                    <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                        background:rgba(${m.color==='pink'?'255,36,228':m.color==='cyan'?'0,242,255':'234,210,255'},0.1);
                        border:1px solid rgba(${m.color==='pink'?'255,36,228':m.color==='cyan'?'0,242,255':'234,210,255'},0.3);flex-shrink:0;">
                        <span class="material-symbols-outlined" style="font-size:14px;color:var(--${m.color==='pink'?'secondary-container':m.color==='cyan'?'primary-container':'tertiary-container'});">${escapeHtml(m.icon)}</span>
                    </div>
                    <div>
                        <div style="font-weight:600;color:var(--on-surface);font-size:14px;">${escapeHtml(m.title)}</div>
                        <div style="font-size:13px;color:var(--on-surface-variant);margin-top:4px;">${escapeHtml(m.detail)}</div>
                    </div>
                </div>`;
        });
    }

    if (structuredExplanation) {
        rationaleHTML += `
            <div style="padding:20px;background:rgba(19,19,21,0.5);border-radius:var(--radius-md);border:1px solid rgba(255,255,255,0.05);color:var(--on-surface-variant);line-height:1.8;white-space:normal;overflow-wrap:anywhere;word-break:break-word;">
                ${formatAiExplanationHtml(structuredExplanation)}
            </div>
        `;
    }

    const severity = ai.severity || 'stable';
    const severityClass = severity === 'critical' ? 'critical' : severity === 'moderate' ? 'moderate' : 'stable';
    const severityScore = ai.severity_score ?? '--';
    const confidence = ai.confidence ?? '--';
    const recommendedResource = escapeHtml(ai.recommended_resource || 'Pending allocation');

    panel.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 class="h3 flex items-center gap-sm">
                <span class="material-symbols-outlined" style="color:var(--primary-container);">psychology</span>
                AI Analysis: ${safePatientName}
            </h3>
            <button onclick="this.closest('#ai-result-panel').remove()" class="icon-btn" title="Close">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px;">
            <div style="text-align:center;padding:16px;background:rgba(19,19,21,0.5);border-radius:var(--radius-md);border:1px solid rgba(255,255,255,0.05);">
                <div class="label-caps text-muted" style="margin-bottom:8px;">Severity Score</div>
                <div class="h2" style="color:${severity==='critical'?'var(--error)':severity==='moderate'?'var(--secondary)':'var(--primary-container)'};">${severityScore}</div>
                <span class="status-pill ${severityClass}" style="margin-top:8px;font-size:11px;">${escapeHtml(String(severityClass).toUpperCase())}</span>
            </div>
            <div style="text-align:center;padding:16px;background:rgba(19,19,21,0.5);border-radius:var(--radius-md);border:1px solid rgba(255,255,255,0.05);">
                <div class="label-caps text-muted" style="margin-bottom:8px;">Confidence</div>
                <div class="h2 text-cyan">${confidence}${confidence === '--' ? '' : '%'}</div>
            </div>
            <div style="text-align:center;padding:16px;background:rgba(19,19,21,0.5);border-radius:var(--radius-md);border:1px solid rgba(255,255,255,0.05);">
                <div class="label-caps text-muted" style="margin-bottom:8px;">Recommended</div>
                <div class="h3 text-pink" style="font-size:16px;">${recommendedResource}</div>
            </div>
        </div>
        <h4 class="h3" style="margin-bottom:16px;font-size:16px;">
            <span class="material-symbols-outlined" style="color:var(--tertiary-container);font-size:18px;">troubleshoot</span>
            Why This Resource?
        </h4>
        ${rationaleHTML}
    `;

    const formCard = document.querySelector('.form-card');
    if (formCard) formCard.parentNode.insertBefore(panel, formCard.nextSibling);
}
