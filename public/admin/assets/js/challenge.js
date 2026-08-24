
// public/admin/assets/js/challenge.js
// Admin Challenge Create/Edit

(function () {
    'use strict';

    const content = document.getElementById('adminContent');
    const pageTitle = document.getElementById('pageTitle');
    const pageSubtitle = document.getElementById('pageSubtitle');

    // Get challenge ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const challengeId = urlParams.get('id');
    const isEdit = !!challengeId;

    let challengeData = null;
    let categories = [];
    let hints = [];
    let files = [];
    let isLoading = false;
    let isSaving = false;

    // ============================================================
    // URL HELPER (same convention as challenges.js)
    // ============================================================
    function appUrl(path) {
        if (window.NCA_API && typeof window.NCA_API.url === 'function') {
            return window.NCA_API.url(path);
        }

        const base = window.NCA_CTF_BASE_URL || '/NCA-CTF/public';
        const cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return `${base}/${cleanPath}`;
    }

    // ============================================================
    // MODAL (shared)
    // ============================================================
    window.AdminModal = {
        open(title, message, confirmText, onConfirm) {
            const overlay = document.getElementById('adminModal');
            const titleEl = document.getElementById('modalTitle');
            const messageEl = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('modalConfirmBtn');

            if (!overlay) return;

            titleEl.textContent = title || 'Confirm Action';
            messageEl.textContent = message || 'Are you sure?';
            confirmBtn.textContent = confirmText || 'Confirm';

            const newConfirm = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirm, confirmBtn);

            newConfirm.addEventListener('click', function () {
                overlay.classList.remove('active');
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });

            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            });

            overlay.classList.add('active');
        },
        close() {
            const overlay = document.getElementById('adminModal');
            if (overlay) overlay.classList.remove('active');
        }
    };

    // ============================================================
    // LOAD DATA
    // ============================================================
    async function loadData() {
        if (isLoading) return;
        isLoading = true;

        try {
            // Load categories
            await loadCategories();

            if (isEdit) {
                // Load challenge data
                const challengeResponse = await fetch(appUrl(`/api/v1/challenges/${challengeId}`), {
                    method: 'GET',
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' }
                });

                if (!challengeResponse.ok) {
                    throw new Error('Challenge not found');
                }

                const challengeJson = await challengeResponse.json();
                challengeData = (challengeJson.data && challengeJson.data.challenge) || null;

                if (!challengeData) {
                    throw new Error('Challenge not found');
                }

                // Load hints
                await loadHints();

                // Load files
                await loadFiles();

                // Update page title
                pageTitle.textContent = `Edit: ${challengeData.title}`;
                pageSubtitle.textContent = `Editing challenge #${challengeData.id}`;

            } else {
                pageTitle.textContent = 'Create Challenge';
                pageSubtitle.textContent = 'Add a new challenge to the competition.';
                challengeData = {
                    title: '',
                    slug: '',
                    description: '',
                    category_id: '',
                    difficulty: 'easy',
                    points: 100,
                    deployment_type: 'static'
                };
            }

            render();

        } catch (error) {
            console.error('Load error:', error);
            content.innerHTML = `
                <div class="admin-error">
                    <div class="error-icon">⚠️</div>
                    <div class="error-text">Unable to load challenge</div>
                    <div class="error-hint">${error.message || 'Please try again later.'}</div>
                    <div class="error-actions">
                        <a href="${appUrl('/admin/challenges.php')}" class="btn btn-primary">Back to Challenges</a>
                        <button onclick="location.reload()" class="btn btn-outline">Retry</button>
                    </div>
                </div>
            `;
        } finally {
            isLoading = false;
        }
    }

    async function loadCategories() {
        try {
            const response = await fetch(appUrl('/api/v1/categories'), {
                method: 'GET',
                credentials: 'include',
                headers: { 'Accept': 'application/json' }
            });

            if (response.ok) {
                const data = await response.json();
                categories = (data.data && data.data.categories) || [];
            }
        } catch (e) {
            console.warn('Could not load categories:', e);
            categories = [];
        }
    }

    async function loadHints() {
        try {
            const response = await fetch(appUrl(`/api/v1/challenges/${challengeId}/hints`), {
                method: 'GET',
                credentials: 'include',
                headers: { 'Accept': 'application/json' }
            });

            if (response.ok) {
                const data = await response.json();
                hints = (data.data && data.data.hints) || [];
            }
        } catch (e) {
            console.warn('Could not load hints:', e);
            hints = [];
        }
    }

    async function loadFiles() {
        try {
            const response = await fetch(appUrl(`/api/v1/challenges/${challengeId}/files`), {
                method: 'GET',
                credentials: 'include',
                headers: { 'Accept': 'application/json' }
            });

            if (response.ok) {
                const data = await response.json();
                files = (data.data && data.data.files) || [];
            }
        } catch (e) {
            console.warn('Could not load files:', e);
            files = [];
        }
    }

    // ============================================================
    // RENDER
    // ============================================================
    function render() {
        const c = challengeData;

        content.innerHTML = `
            <form id="challengeForm" class="admin-form" onsubmit="return false;">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="form-section-title">Basic Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" value="${escapeHtml(c.title || '')}" required>
                        </div>
                        <div class="form-group">
                            <label for="slug">Slug</label>
                            <input type="text" id="slug" value="${escapeHtml(c.slug || '')}" placeholder="auto-generated if empty">
                            <span class="help-text">URL-friendly identifier. Leave blank for auto-generation.</span>
                        </div>
                    </div>
                    <div class="form-row full">
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" rows="6" required>${escapeHtml(c.description || '')}</textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" required>
                                <option value="">Select a category...</option>
                                ${categories.map(cat => `
                                    <option value="${cat.id}" ${c.category_id == cat.id ? 'selected' : ''}>
                                        ${escapeHtml(cat.name)}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="difficulty">Difficulty *</label>
                            <select id="difficulty" required>
                                <option value="easy" ${c.difficulty === 'easy' ? 'selected' : ''}>Easy</option>
                                <option value="medium" ${c.difficulty === 'medium' ? 'selected' : ''}>Medium</option>
                                <option value="hard" ${c.difficulty === 'hard' ? 'selected' : ''}>Hard</option>
                                <option value="insane" ${c.difficulty === 'insane' ? 'selected' : ''}>Insane</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="points">Points *</label>
                            <input type="number" id="points" value="${c.points || 100}" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="deployment_type">Deployment Type *</label>
                            <select id="deployment_type" required>
                                <option value="DOWNLOAD" ${c.deployment_type === 'DOWNLOAD' ? 'selected' : ''}>Download / File-based</option>
                                <option value="HTTP" ${c.deployment_type === 'HTTP' ? 'selected' : ''}>HTTP</option>
                                <option value="TCP" ${c.deployment_type === 'TCP' ? 'selected' : ''}>TCP</option>
                            </select>
                            <span class="help-text">Deployment type as recognized by the backend (download, HTTP, or TCP).</span>
                        </div>
                    </div>
                </div>

                <!-- Flag -->
                <div class="form-section">
                    <h3 class="form-section-title">Flag</h3>
                    ${isEdit ? `
                        <div class="flag-status">
                            <span class="flag-icon">🔒</span>
                            <span class="flag-text">✓ Flag configured. The current flag cannot be viewed.</span>
                        </div>
                        <div style="margin-top:12px;">
                            <button type="button" class="btn btn-primary" onclick="ChallengeForm.showFlagForm()">Replace Flag</button>
                        </div>
                        <div id="flagReplaceForm" style="display:none; margin-top:12px;">
                            <div class="form-group">
                                <label for="newFlag">New Flag</label>
                                <input type="text" id="newFlag" placeholder="flag{your_flag_here}">
                                <span class="help-text">Enter the new flag value. It will be stored securely.</span>
                            </div>
                            <div style="margin-top:8px; display:flex; gap:8px;">
                                <button type="button" class="btn btn-success" onclick="ChallengeForm.updateFlag()">Update Flag</button>
                                <button type="button" class="btn btn-outline" onclick="ChallengeForm.hideFlagForm()">Cancel</button>
                            </div>
                        </div>
                    ` : `
                        <div class="form-group">
                            <label for="flag">Flag *</label>
                            <input type="text" id="flag" placeholder="flag{your_flag_here}" required>
                            <span class="help-text">The flag value that participants must submit to solve this challenge.</span>
                        </div>
                    `}
                </div>

                <!-- Hints (edit mode only -- hints require an existing challenge_id) -->
                ${isEdit ? `
                <div class="form-section">
                    <h3 class="form-section-title">Hints</h3>
                    <div id="hintsContainer">
                        ${hints.length === 0 ? `
                            <div class="admin-empty-state" style="padding:16px;">
                                <div class="empty-hint">No hints added yet.</div>
                            </div>
                        ` : `
                            <div class="admin-items-list">
                                ${hints.map(h => `
                                    <div class="admin-item" data-hint-id="${h.id}">
                                        <div class="item-info">
                                            <div class="item-title">${escapeHtml(h.content || 'Untitled hint')}</div>
                                            <div class="item-meta">Cost: ${h.cost || 0} points</div>
                                        </div>
                                        <div class="item-actions">
                                            <button class="btn btn-outline btn-xs" onclick="ChallengeForm.editHint(${h.id})">Edit</button>
                                            <button class="btn btn-danger btn-xs" onclick="ChallengeForm.deleteHint(${h.id})">Delete</button>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        `}
                    </div>
                    <div style="margin-top:12px;">
                        <button type="button" class="btn btn-outline" onclick="ChallengeForm.showAddHint()">+ Add Hint</button>
                    </div>
                    <div id="hintFormContainer" style="display:none; margin-top:12px; padding:16px; background:var(--admin-bg); border-radius:var(--admin-radius); border:1px solid var(--admin-border);">
                        <div class="form-group" style="margin-bottom:10px;">
                            <label for="hintContent">Hint Content</label>
                            <textarea id="hintContent" rows="3" placeholder="Enter hint text..."></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom:10px;">
                            <label for="hintCost">Cost (points)</label>
                            <input type="number" id="hintCost" value="10" min="0">
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button type="button" class="btn btn-success" onclick="ChallengeForm.saveHint()">Save Hint</button>
                            <button type="button" class="btn btn-outline" onclick="ChallengeForm.cancelHint()">Cancel</button>
                        </div>
                        <input type="hidden" id="editHintId" value="">
                    </div>
                </div>
                ` : `
                <div class="form-section">
                    <h3 class="form-section-title">Hints</h3>
                    <div class="admin-empty-state" style="padding:16px;">
                        <div class="empty-hint">Save the challenge first, then add hints.</div>
                    </div>
                </div>
                `}

                <!-- Files (edit mode only -- files require an existing challenge_id) -->
                ${isEdit ? `
                <div class="form-section">
                    <h3 class="form-section-title">Files</h3>
                    <div id="filesContainer">
                        ${files.length === 0 ? `
                            <div class="admin-empty-state" style="padding:16px;">
                                <div class="empty-hint">No files attached.</div>
                            </div>
                        ` : `
                            <div class="admin-items-list">
                                ${files.map(f => `
                                    <div class="admin-item" data-file-id="${f.id}">
                                        <div class="item-info">
                                            <div class="item-title">📎 ${escapeHtml(f.original_name || f.filename)}</div>
                                            <div class="item-meta">${f.file_size ? `${Math.round(f.file_size / 1024)} KB` : ''} ${f.uploaded_at ? `· Uploaded ${new Date(f.uploaded_at).toLocaleDateString()}` : ''}</div>
                                        </div>
                                        <div class="item-actions">
                                            <a href="${appUrl(`/api/v1/challenge-files/${f.id}/download`)}" class="btn btn-outline btn-xs" target="_blank">Download</a>
                                            <button class="btn btn-danger btn-xs" onclick="ChallengeForm.deleteFile(${f.id})">Delete</button>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        `}
                    </div>
                    <div style="margin-top:12px;">
                        <form id="fileUploadForm" style="display:inline;">
                            <input type="file" id="fileInput" style="display:none;" onchange="ChallengeForm.uploadFile(this)">
                            <button type="button" class="btn btn-outline" onclick="document.getElementById('fileInput').click()">+ Upload File</button>
                        </form>
                        <div id="uploadProgress" style="display:none; margin-top:8px; color:var(--admin-text-muted);">
                            Uploading...
                        </div>
                    </div>
                </div>
                ` : `
                <div class="form-section">
                    <h3 class="form-section-title">Files</h3>
                    <div class="admin-empty-state" style="padding:16px;">
                        <div class="empty-hint">Save the challenge first, then attach files.</div>
                    </div>
                </div>
                `}

                <!-- Status (edit only) -->
                ${isEdit ? `
                    <div class="form-section">
                        <h3 class="form-section-title">Status & Publishing</h3>
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <span style="color:var(--admin-text-muted);">Current status:</span>
                            <span class="status-badge status-${c.status}">${c.status || 'draft'}</span>
                            ${c.status === 'draft' ? `
                                <button type="button" class="btn btn-success" onclick="ChallengeForm.publish()">Publish</button>
                            ` : ''}
                            ${c.status === 'published' ? `
                                <button type="button" class="btn btn-warning" onclick="ChallengeForm.pause()">Pause</button>
                                <button type="button" class="btn btn-danger" onclick="ChallengeForm.archive()">Archive</button>
                            ` : ''}
                            ${c.status === 'paused' ? `
                                <button type="button" class="btn btn-success" onclick="ChallengeForm.publish()">Publish</button>
                                <button type="button" class="btn btn-danger" onclick="ChallengeForm.archive()">Archive</button>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}

                <!-- Form Actions -->
                <div style="display:flex; gap:12px; margin-top:16px; padding-top:16px; border-top:1px solid var(--admin-border);">
                    <button type="submit" class="btn btn-primary" id="saveBtn">${isEdit ? 'Update Challenge' : 'Create Challenge'}</button>
                    <a href="${appUrl('/admin/challenges.php')}" class="btn btn-outline">Cancel</a>
                    ${isEdit ? `
                        <button type="button" class="btn btn-danger" style="margin-left:auto;" onclick="ChallengeForm.deleteChallenge()">Delete Challenge</button>
                    ` : ''}
                </div>
            </form>
        `;

        // Bind form submit
        document.getElementById('challengeForm').addEventListener('submit', function (e) {
            e.preventDefault();
            ChallengeForm.save();
        });
    }

    // ============================================================
    // CHALLENGE FORM ACTIONS
    // ============================================================
    window.ChallengeForm = {
        async save() {
            if (isSaving) return;
            isSaving = true;

            const title = document.getElementById('title').value.trim();
            const slug = document.getElementById('slug').value.trim();
            const description = document.getElementById('description').value.trim();
            const category_id = document.getElementById('category_id').value;
            const difficulty = document.getElementById('difficulty').value;
            const points = parseInt(document.getElementById('points').value);
            const deployment_type = document.getElementById('deployment_type').value;

            // Validate
            if (!title) { alert('Title is required.'); isSaving = false; return; }
            if (!description) { alert('Description is required.'); isSaving = false; return; }
            if (!category_id) { alert('Category is required.'); isSaving = false; return; }
            if (!points || points < 1) { alert('Points must be at least 1.'); isSaving = false; return; }

            const data = {
                title,
                slug: slug || undefined,
                description,
                category_id: parseInt(category_id),
                difficulty,
                points,
                deployment_type
            };

            // Flag is required for new challenges, but the challenge-create
            // endpoint does not accept a flag field -- it must be created
            // separately via POST /api/v1/challenges/{id}/flag once the
            // challenge exists (see app/Controllers/FlagController.php).
            let pendingFlag = null;
            if (!isEdit) {
                pendingFlag = document.getElementById('flag').value.trim();
                if (!pendingFlag) { alert('Flag is required.'); isSaving = false; return; }
            }

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const url = isEdit ? appUrl(`/api/v1/challenges/${challengeId}`) : appUrl('/api/v1/challenges');
                const method = isEdit ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(data)
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Save failed');
                }

                const result = await response.json();
                const savedId = (result.data && result.data.challenge && result.data.challenge.id) || challengeId;

                if (!isEdit) {
                    // Create the flag now that the challenge exists.
                    const flagResponse = await fetch(appUrl(`/api/v1/challenges/${savedId}/flag`), {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({ flag: pendingFlag })
                    });

                    if (!flagResponse.ok) {
                        const flagError = await flagResponse.json();
                        throw new Error(flagError.message || 'Challenge was created, but the flag could not be saved.');
                    }

                    // Redirect to edit page
                    window.location.href = appUrl(`/admin/challenge.php?id=${savedId}`);
                } else {
                    // Reload to reflect changes
                    await loadData();
                    alert('Challenge updated successfully!');
                }

            } catch (error) {
                console.error('Save error:', error);
                alert(`Error: ${error.message || 'Unable to save challenge.'}`);
            } finally {
                isSaving = false;
            }
        },

        async updateFlag() {
            const newFlag = document.getElementById('newFlag').value.trim();
            if (!newFlag) {
                alert('Please enter a new flag value.');
                return;
            }

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch(appUrl(`/api/v1/challenges/${challengeId}/flag`), {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ flag: newFlag })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Flag update failed');
                }

                alert('Flag updated successfully!');
                document.getElementById('newFlag').value = '';
                ChallengeForm.hideFlagForm();

            } catch (error) {
                console.error('Flag update error:', error);
                alert(`Error: ${error.message || 'Unable to update flag.'}`);
            }
        },

        showFlagForm() {
            document.getElementById('flagReplaceForm').style.display = 'block';
        },

        hideFlagForm() {
            document.getElementById('flagReplaceForm').style.display = 'none';
        },

        async publish() {
            AdminModal.open(
                'Publish Challenge',
                `Publish "${challengeData.title}"?\n\nParticipants will be able to see and solve this challenge.`,
                'Publish Challenge',
                async () => {
                    await updateStatus('publish');
                }
            );
        },

        async pause() {
            AdminModal.open(
                'Pause Challenge',
                `Pause "${challengeData.title}"?\n\nThe challenge will no longer be active.`,
                'Pause Challenge',
                async () => {
                    await updateStatus('pause');
                }
            );
        },

        async archive() {
            AdminModal.open(
                'Archive Challenge',
                `Archive "${challengeData.title}"?\n\nThe challenge will be archived and no longer active.`,
                'Archive Challenge',
                async () => {
                    await updateStatus('archive');
                }
            );
        },

        async deleteChallenge() {
            AdminModal.open(
                'Delete Challenge',
                `Delete "${challengeData.title}"?\n\nThis action permanently removes the challenge and cannot be undone.`,
                'Delete Challenge',
                async () => {
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const response = await fetch(appUrl(`/api/v1/challenges/${challengeId}`), {
                            method: 'DELETE',
                            credentials: 'include',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });

                        if (!response.ok) {
                            const error = await response.json();
                            throw new Error(error.message || 'Delete failed');
                        }

                        window.location.href = appUrl('/admin/challenges.php');

                    } catch (error) {
                        console.error('Delete error:', error);
                        alert(`Error: ${error.message || 'Unable to delete challenge.'}`);
                    }
                }
            );
        },

        // ============================================================
        // HINT MANAGEMENT
        // ============================================================
        showAddHint() {
            document.getElementById('hintFormContainer').style.display = 'block';
            document.getElementById('editHintId').value = '';
            document.getElementById('hintContent').value = '';
            document.getElementById('hintCost').value = '10';
            document.getElementById('hintContent').focus();
        },

        editHint(id) {
            const hint = hints.find(h => h.id === id);
            if (!hint) return;

            document.getElementById('hintFormContainer').style.display = 'block';
            document.getElementById('editHintId').value = id;
            document.getElementById('hintContent').value = hint.content || '';
            document.getElementById('hintCost').value = hint.cost || 0;
            document.getElementById('hintContent').focus();
        },

        cancelHint() {
            document.getElementById('hintFormContainer').style.display = 'none';
            document.getElementById('editHintId').value = '';
            document.getElementById('hintContent').value = '';
            document.getElementById('hintCost').value = '10';
        },

        async saveHint() {
            const id = document.getElementById('editHintId').value;
            const content = document.getElementById('hintContent').value.trim();
            const cost = parseInt(document.getElementById('hintCost').value) || 0;

            if (!content) {
                alert('Please enter hint content.');
                return;
            }

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const url = id ? appUrl(`/api/v1/challenge-hints/${id}`) : appUrl(`/api/v1/challenges/${challengeId}/hints`);
                const method = id ? 'PUT' : 'POST';
                const data = { content, cost };

                const response = await fetch(url, {
                    method,
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(data)
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Hint save failed');
                }

                // Reload hints
                await loadHints();
                ChallengeForm.cancelHint();
                render();

            } catch (error) {
                console.error('Hint save error:', error);
                alert(`Error: ${error.message || 'Unable to save hint.'}`);
            }
        },

        async deleteHint(id) {
            const hint = hints.find(h => h.id === id);
            if (!hint) return;

            AdminModal.open(
                'Delete Hint',
                `Delete this hint?\n\n"${hint.content || 'Untitled hint'}"`,
                'Delete Hint',
                async () => {
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const response = await fetch(appUrl(`/api/v1/challenge-hints/${id}`), {
                            method: 'DELETE',
                            credentials: 'include',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });

                        if (!response.ok) {
                            const error = await response.json();
                            throw new Error(error.message || 'Delete failed');
                        }

                        await loadHints();
                        render();

                    } catch (error) {
                        console.error('Hint delete error:', error);
                        alert(`Error: ${error.message || 'Unable to delete hint.'}`);
                    }
                }
            );
        },

        // ============================================================
        // FILE MANAGEMENT
        // ============================================================
        async uploadFile(input) {
            if (!input.files || !input.files[0]) return;

            const file = input.files[0];
            const formData = new FormData();
            formData.append('file', file);

            const progressEl = document.getElementById('uploadProgress');
            progressEl.style.display = 'block';
            progressEl.textContent = `Uploading ${file.name}...`;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch(appUrl(`/api/v1/challenges/${challengeId}/files`), {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'X-CSRF-TOKEN': csrf
                    },
                    body: formData
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Upload failed');
                }

                await loadFiles();
                render();
                progressEl.style.display = 'none';
                input.value = '';

            } catch (error) {
                console.error('Upload error:', error);
                progressEl.style.display = 'none';
                alert(`Error: ${error.message || 'Unable to upload file.'}`);
            }
        },

        async deleteFile(id) {
            const file = files.find(f => f.id === id);
            if (!file) return;

            AdminModal.open(
                'Delete File',
                `Delete "${file.original_name || file.filename}"?\n\nThis action cannot be undone.`,
                'Delete File',
                async () => {
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const response = await fetch(appUrl(`/api/v1/challenge-files/${id}`), {
                            method: 'DELETE',
                            credentials: 'include',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });

                        if (!response.ok) {
                            const error = await response.json();
                            throw new Error(error.message || 'Delete failed');
                        }

                        await loadFiles();
                        render();

                    } catch (error) {
                        console.error('File delete error:', error);
                        alert(`Error: ${error.message || 'Unable to delete file.'}`);
                    }
                }
            );
        }
    };

    // ============================================================
    // HELPERS
    // ============================================================
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // `action` must be one of 'publish' | 'pause' | 'archive' -- these map
    // directly to the dedicated lifecycle endpoints; there is no generic
    // status PUT endpoint on the backend (see routes/api.php).
    async function updateStatus(action) {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch(appUrl(`/api/v1/challenges/${challengeId}/${action}`), {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                }
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Status update failed');
            }

            await loadData();

        } catch (error) {
            console.error('Status update error:', error);
            alert(`Error: ${error.message || 'Unable to update status.'}`);
        }
    }

    // ============================================================
    // INIT
    // ============================================================
    function init() {
        if (window.Admin && window.Admin.isAuthorized()) {
            loadData();
        } else {
            document.addEventListener('admin:authReady', function (e) {
                if (e.detail.authorized) {
                    loadData();
                }
            });
        }
    }

    init();

})();