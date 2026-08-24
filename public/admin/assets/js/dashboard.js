
// public/admin/assets/js/dashboard.js
// Admin Dashboard

(function () {
    'use strict';

    const content = document.getElementById('adminContent');

    function adminUrl(path) {
        if (window.NCA_API && typeof window.NCA_API.url === 'function') {
            return window.NCA_API.url(path);
        }
        const base = window.NCA_CTF_BASE_URL || '/NCA-CTF/public';
        const cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return `${base}/${cleanPath}`;
    }

    // ============================================================
    // RENDER DASHBOARD
    // ============================================================
    async function loadDashboard() {
        try {
            // Show loading
            content.innerHTML = `
                <div class="admin-loading">
                    <div class="spinner"></div>
                    Loading dashboard...
                </div>
            `;

            // Fetch recent challenges (limit 5)
            const response = await fetch(
                (window.NCA_CTF_BASE_URL || '/NCA-CTF/public') +
                '/api/v1/challenges?limit=5&page=1',
                {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to load challenges: ${response.status}`);
            }

            const data = await response.json();
            const challenges = (data.data && data.data.challenges) || [];

            // Also fetch categories count for some context
            const catResponse = await fetch(
                (window.NCA_CTF_BASE_URL || '/NCA-CTF/public') +
                '/api/v1/categories',
                {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );
            const catData = catResponse.ok
                ? await catResponse.json()
                : { data: { categories: [] } };
            const categories = (catData.data && catData.data.categories) || [];

            // Render dashboard
            renderDashboard(challenges, categories);

        } catch (error) {
            console.error('Dashboard load error:', error);
            content.innerHTML = `
                <div class="admin-error">
                    <div class="error-icon">⚠️</div>
                    <div class="error-text">Unable to load dashboard</div>
                    <div class="error-hint">${error.message || 'Please try again later.'}</div>
                    <div class="error-actions">
                        <button onclick="location.reload()" class="btn btn-primary">Retry</button>
                    </div>
                </div>
            `;
        }
    }

    function renderDashboard(challenges, categories) {
        // Calculate stats from available data
        const total = challenges.length;
        const published = challenges.filter(c => c.status === 'published').length;
        const draft = challenges.filter(c => c.status === 'draft').length;
        const paused = challenges.filter(c => c.status === 'paused').length;
        const archived = challenges.filter(c => c.status === 'archived').length;
        const categoryCount = categories.length;

        content.innerHTML = `
            <!-- Stats -->
            <div class="admin-stats-grid">
                <div class="stat-card">
                    <div class="stat-number">${total}</div>
                    <div class="stat-label">Total Challenges</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number accent">${published}</div>
                    <div class="stat-label">Published</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${draft}</div>
                    <div class="stat-label">Draft</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${paused}</div>
                    <div class="stat-label">Paused</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${archived}</div>
                    <div class="stat-label">Archived</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${categoryCount}</div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-dashboard-actions">
                <a href="${adminUrl('/admin/challenges.php')}" class="btn btn-primary">📋 Manage Challenges</a>
                <a href="${adminUrl('/admin/challenge.php')}" class="btn btn-success">➕ Create New Challenge</a>
            </div>

            <!-- Recent Challenges -->
            <h2 style="font-size:16px; font-weight:600; margin:0 0 12px 0; color:var(--admin-text);">Recent Challenges</h2>
            ${challenges.length === 0 ? `
                <div class="admin-empty-state">
                    <div class="empty-icon">📭</div>
                    <div class="empty-text">No challenges found</div>
                    <div class="empty-hint">Create your first challenge to get started.</div>
                </div>
            ` : `
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Challenge</th>
                                <th>Category</th>
                                <th>Difficulty</th>
                                <th>Points</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${challenges.map(c => `
                                <tr>
                                    <td><a href="${adminUrl(`/admin/challenge.php?id=${c.id}`)}" style="color:var(--admin-text); text-decoration:none;">${escapeHtml(c.title)}</a></td>
                                    <td>${c.category ? escapeHtml(typeof c.category === 'string' ? c.category : c.category.name) : '-'}</td>
                                    <td><span class="diff-${c.difficulty}">${c.difficulty || '-'}</span></td>
                                    <td>${c.points || 0}</td>
                                    <td><span class="status-badge status-${c.status}">${c.status || 'draft'}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `}
        `;
    }

    // Simple escape helper
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================================
    // INIT - Wait for auth to be ready
    // ============================================================
    function init() {
        // Wait for auth check to complete
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            // Check if admin.js has completed auth
            if (window.Admin && window.Admin.isAuthorized()) {
                loadDashboard();
            } else {
                // Listen for auth ready event
                document.addEventListener('admin:authReady', function (e) {
                    if (e.detail.authorized) {
                        loadDashboard();
                    }
                });
            }
        } else {
            document.addEventListener('DOMContentLoaded', function () {
                if (window.Admin && window.Admin.isAuthorized()) {
                    loadDashboard();
                } else {
                    document.addEventListener('admin:authReady', function (e) {
                        if (e.detail.authorized) {
                            loadDashboard();
                        }
                    });
                }
            });
        }
    }

    init();

})();