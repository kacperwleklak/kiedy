document.addEventListener('DOMContentLoaded', () => {
    // --- Name Modal Logic ---
    const nameModal = document.getElementById('nameModal');
    const userNameInput = document.getElementById('userNameInput');
    const saveNameBtn = document.getElementById('saveNameBtn');

    if (!HAS_NAME && nameModal) {
        saveNameBtn.addEventListener('click', async () => {
            const name = userNameInput.value.trim();
            if (!name) return;

            saveNameBtn.disabled = true;
            saveNameBtn.textContent = 'Saving...';

            try {
                const res = await fetch('api/set_user_name.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name })
                });

                if (res.ok) {
                    nameModal.classList.add('hidden');
                }
            } catch (e) {
                console.error(e);
            }
            saveNameBtn.disabled = false;
            saveNameBtn.textContent = 'Save & Continue';
        });
    }

    // --- Copy Link Logic ---
    const copyBtn = document.getElementById('copyLinkBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(window.location.href);
            const original = copyBtn.textContent;
            copyBtn.textContent = 'Copied!';
            setTimeout(() => { copyBtn.textContent = original; }, 2000);
        });
    }

    // --- Edit Mode & Grid Drawing Logic ---
    let isEditMode = false;
    let isDrawing = false;
    let targetStatus = ''; // 'available', 'maybe', ''

    let localChanges = {}; // Maps "dayId_timeSlot" -> newStatus

    const toggleEditModeBtn = document.getElementById('toggleEditModeBtn');
    const saveAvailabilityBtn = document.getElementById('saveAvailabilityBtn');
    const readOnlyLegend = document.getElementById('readOnlyLegend');
    const editModeLegend = document.getElementById('editModeLegend');
    const table = document.querySelector('.calendar-table');

    if (toggleEditModeBtn) {
        toggleEditModeBtn.addEventListener('click', () => {
            // Require name first
            if (!HAS_NAME && !nameModal.classList.contains('hidden')) {
                nameModal.classList.remove('hidden');
                return;
            }

            isEditMode = true;
            toggleEditModeBtn.classList.add('hidden');
            saveAvailabilityBtn.classList.remove('hidden');
            readOnlyLegend.classList.add('hidden');
            editModeLegend.classList.remove('hidden');

            // visually switch grid to edit mode
            document.querySelector('.calendar-grid-wrapper').classList.add('is-editing');

            // clear local changes
            localChanges = {};

            // Apply current user status to UI (override heatmap)
            document.querySelectorAll('.grid-cell').forEach(cell => {
                const s = cell.dataset.userStatus;
                cell.classList.remove('is-available', 'is-maybe');
                if (s === 'available') cell.classList.add('is-available');
                if (s === 'maybe') cell.classList.add('is-maybe');
            });
        });
    }

    if (saveAvailabilityBtn) {
        saveAvailabilityBtn.addEventListener('click', async () => {
            saveAvailabilityBtn.disabled = true;
            saveAvailabilityBtn.textContent = 'Saving...';

            // Prepare payload
            const payload = Object.entries(localChanges).map(([key, status]) => {
                const [dayId, timeSlot] = key.split('_');
                return { calendar_day_id: dayId, time_slot: timeSlot, status: status };
            });

            if (payload.length > 0) {
                try {
                    // Send bulk update or make multiple requests. 
                    // Our api only handles 1 right now: 
                    // Let's modify the API simultaneously or just fire multiple fetchs. Wait, multiple fetch is easier if API isn't updated.
                    const promises = payload.map(item => fetch('api/update_availability.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(item)
                    }));
                    await Promise.all(promises);
                } catch (e) {
                    console.error("Save failed", e);
                }
            }

            // reload page to see new aggregated heatmap
            window.location.reload();
        });
    }

    if (table) {
        table.addEventListener('contextmenu', e => {
            if (isEditMode) e.preventDefault();
        });

        table.addEventListener('mousedown', (e) => {
            if (!isEditMode) return;
            const cell = e.target.closest('.grid-cell');
            if (!cell) return;

            e.preventDefault();
            isDrawing = true;

            const isRightClick = e.button === 2;
            const drawType = isRightClick ? 'maybe' : 'available';

            if (cell.classList.contains(`is-${drawType}`)) {
                targetStatus = '';
            } else {
                targetStatus = drawType;
            }

            updateCell(cell, targetStatus);
        });

        table.addEventListener('mouseover', (e) => {
            if (!isDrawing || !isEditMode) return;
            const cell = e.target.closest('.grid-cell');
            if (!cell) return;

            updateCell(cell, targetStatus);
        });

        document.addEventListener('mouseup', () => {
            isDrawing = false;
        });

        table.addEventListener('touchstart', (e) => {
            if (!isEditMode) return;
            const touch = e.touches[0];
            const cell = document.elementFromPoint(touch.clientX, touch.clientY)?.closest('.grid-cell');
            if (!cell) return;

            isDrawing = true;
            if (cell.classList.contains('is-available')) {
                targetStatus = 'maybe';
            } else if (cell.classList.contains('is-maybe')) {
                targetStatus = '';
            } else {
                targetStatus = 'available';
            }
            updateCell(cell, targetStatus);
        });

        table.addEventListener('touchmove', (e) => {
            if (!isDrawing || !isEditMode) return;
            e.preventDefault();
            const touch = e.touches[0];
            const cell = document.elementFromPoint(touch.clientX, touch.clientY)?.closest('.grid-cell');
            if (cell) updateCell(cell, targetStatus);
        });

        table.addEventListener('touchend', () => {
            isDrawing = false;
        });
    }

    function updateCell(cell, status) {
        const currentStatus = cell.classList.contains('is-available') ? 'available' :
            (cell.classList.contains('is-maybe') ? 'maybe' : '');

        if (currentStatus === status) return;

        cell.classList.remove('is-available', 'is-maybe');
        if (status === 'available') cell.classList.add('is-available');
        if (status === 'maybe') cell.classList.add('is-maybe');

        const dayId = cell.dataset.dayId;
        const timeSlot = cell.dataset.time;

        localChanges[`${dayId}_${timeSlot}`] = status;
    }

    // --- Hover participant logic ---
    const voterBadges = document.querySelectorAll('.voter-badge');
    const allCells = document.querySelectorAll('.grid-cell');

    voterBadges.forEach(badge => {
        badge.addEventListener('mouseenter', () => {
            const vid = badge.dataset.vid;
            allCells.forEach(cell => {
                const vidsRaw = cell.dataset.availableVids;
                if (vidsRaw && vidsRaw !== '[]') {
                    try {
                        const vids = JSON.parse(vidsRaw);
                        if (vids.includes(vid)) {
                            cell.classList.add('highlight-available');
                        }
                    } catch (e) { }
                }

                const maybeVidsRaw = cell.dataset.maybeVids;
                if (maybeVidsRaw && maybeVidsRaw !== '[]') {
                    try {
                        const maybeVids = JSON.parse(maybeVidsRaw);
                        if (maybeVids.includes(vid)) {
                            cell.classList.add('highlight-maybe');
                        }
                    } catch (e) { }
                }
            });
        });

        badge.addEventListener('mouseleave', () => {
            allCells.forEach(cell => {
                cell.classList.remove('highlight-available', 'highlight-maybe');
            });
        });
    });

    // --- Hover cell logic (to highlight participants) ---
    allCells.forEach(cell => {
        cell.addEventListener('mouseenter', () => {
            if (isEditMode) return; // Don't do this while editing

            const avVidsRaw = cell.dataset.availableVids;
            const maybeVidsRaw = cell.dataset.maybeVids;

            let avVids = [];
            let maybeVids = [];

            try { if (avVidsRaw && avVidsRaw !== '[]') avVids = JSON.parse(avVidsRaw); } catch (e) { }
            try { if (maybeVidsRaw && maybeVidsRaw !== '[]') maybeVids = JSON.parse(maybeVidsRaw); } catch (e) { }

            voterBadges.forEach(badge => {
                const vid = badge.dataset.vid;
                if (avVids.includes(vid)) {
                    badge.classList.add('badge-highlight-available');
                } else if (maybeVids.includes(vid)) {
                    badge.classList.add('badge-highlight-maybe');
                }
            });
        });

        cell.addEventListener('mouseleave', () => {
            voterBadges.forEach(badge => {
                badge.classList.remove('badge-highlight-available', 'badge-highlight-maybe');
            });
        });
    });
});
