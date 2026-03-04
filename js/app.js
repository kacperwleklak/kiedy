document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('dateInput');
    const addDateBtn = document.getElementById('addDateBtn');
    const selectedDatesList = document.getElementById('selectedDatesList');
    const datesData = document.getElementById('datesData');
    const dateError = document.getElementById('dateError');
    const form = document.getElementById('createCalendarForm');

    // New Elements
    const modeBtns = document.querySelectorAll('.mode-btn');
    const modePanels = document.querySelectorAll('.date-mode-panel');
    const presetBtns = document.querySelectorAll('.preset-btn');
    const rangeStart = document.getElementById('rangeStart');
    const rangeEnd = document.getElementById('rangeEnd');
    const addRangeBtn = document.getElementById('addRangeBtn');
    const clearDatesBtn = document.getElementById('clearDatesBtn');
    const selectedDatesHeader = document.getElementById('selectedDatesHeader');

    let selectedDates = [];

    // Form logic on index.php
    if (form) {
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];

        dateInput.setAttribute('min', todayStr);
        rangeStart.setAttribute('min', todayStr);
        rangeEnd.setAttribute('min', todayStr);

        // Mode Switching
        modeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                modeBtns.forEach(b => b.classList.remove('active'));
                modePanels.forEach(p => p.classList.add('hidden'));

                btn.classList.add('active');
                document.getElementById(`mode-${btn.dataset.mode}`).classList.remove('hidden');
                document.getElementById(`mode-${btn.dataset.mode}`).classList.add('active');
            });
        });

        // Add Specific Date
        addDateBtn.addEventListener('click', () => {
            const dateValue = dateInput.value;
            if (!dateValue) return;

            if (!selectedDates.includes(dateValue)) {
                selectedDates.push(dateValue);
                // Sort dates chronologically
                selectedDates.sort();
                updateDatesInput();
                renderDateBadges();
                dateError.classList.add('hidden');
            }
            dateInput.value = '';
        });

        dateInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addDateBtn.click();
            }
        });

        // Clear All Dates
        clearDatesBtn.addEventListener('click', () => {
            selectedDates = [];
            updateDatesInput();
            renderDateBadges();
            dateError.classList.add('hidden');
        });

        // Add Range
        addRangeBtn.addEventListener('click', () => {
            const start = new Date(rangeStart.value);
            const end = new Date(rangeEnd.value);

            if (!rangeStart.value || !rangeEnd.value) return;
            if (start > end) return;

            let current = new Date(start);
            while (current <= end) {
                const dateStr = current.toISOString().split('T')[0];
                if (!selectedDates.includes(dateStr)) {
                    selectedDates.push(dateStr);
                }
                current.setDate(current.getDate() + 1);
            }

            rangeStart.value = '';
            rangeEnd.value = '';

            selectedDates.sort();
            updateDatesInput();
            renderDateBadges();
        });

        // Preset Dates Math
        presetBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const preset = btn.dataset.preset;
                const now = new Date();

                // Set to start of today (local time)
                now.setHours(0, 0, 0, 0);

                let start = new Date(now);
                let end = new Date(now);

                if (preset === 'this-week') {
                    // Monday to Sunday of current week
                    const dayOfWeek = now.getDay() === 0 ? 7 : now.getDay(); // 1(Mon) to 7(Sun)
                    const daysToMonday = dayOfWeek - 1;
                    const daysToSunday = 7 - dayOfWeek;

                    start.setDate(now.getDate() - daysToMonday);
                    end.setDate(now.getDate() + daysToSunday);
                } else if (preset === 'this-weekend') {
                    // This week's Friday to Sunday
                    const dayOfWeek = now.getDay() === 0 ? 7 : now.getDay();
                    const daysToFriday = 5 - dayOfWeek;

                    start.setDate(now.getDate() + daysToFriday);
                    end = new Date(start);
                    end.setDate(start.getDate() + 2); // Fri + 2 = Sun
                } else if (preset === 'next-week') {
                    // Next Monday to next Sunday
                    const dayOfWeek = now.getDay() === 0 ? 7 : now.getDay();
                    const daysToNextMonday = 8 - dayOfWeek;

                    start.setDate(now.getDate() + daysToNextMonday);
                    end = new Date(start);
                    end.setDate(start.getDate() + 6);
                }

                // Add days
                let current = new Date(start);
                while (current <= end) {
                    const dateStr = current.toISOString().split('T')[0];
                    if (!selectedDates.includes(dateStr)) {
                        selectedDates.push(dateStr);
                    }
                    current.setDate(current.getDate() + 1);
                }

                selectedDates.sort();
                updateDatesInput();
                renderDateBadges();
            });
        });

        form.addEventListener('submit', (e) => {
            if (selectedDates.length === 0) {
                e.preventDefault();
                dateError.classList.remove('hidden');
            }
        });
    }

    function removeDate(dateToRemove) {
        selectedDates = selectedDates.filter(d => d !== dateToRemove);
        updateDatesInput();
        renderDateBadges();
    }

    function updateDatesInput() {
        datesData.value = JSON.stringify(selectedDates);
    }

    function renderDateBadges() {
        selectedDatesList.innerHTML = '';

        if (selectedDates.length > 0) {
            selectedDatesHeader.classList.remove('hidden');
        } else {
            selectedDatesHeader.classList.add('hidden');
        }

        selectedDates.forEach(date => {
            const badge = document.createElement('div');
            badge.className = 'date-badge';

            // Format for display (en-GB uses 24h & day/month order by default)
            const displayDate = new Date(date).toLocaleDateString('en-GB', {
                weekday: 'short', month: 'short', day: 'numeric'
            });

            badge.innerHTML = `
                <span>${displayDate}</span>
                <button type="button" aria-label="Remove ${date}">&times;</button>
            `;

            badge.querySelector('button').addEventListener('click', () => {
                removeDate(date);
            });

            selectedDatesList.appendChild(badge);
        });
    }
});
