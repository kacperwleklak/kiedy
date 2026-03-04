<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiedy - Group Availability</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="background-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <main class="container glass-card enter-animation">
        <header class="text-center">
            <h1 class="gradient-text">Kiedy?</h1>
            <p class="subtitle">Find the perfect time for your group easily.</p>
        </header>

        <form id="createCalendarForm" action="api/create_calendar.php" method="POST">
            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" placeholder="e.g. Board Game Night" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description (Optional)</label>
                <textarea id="description" name="description" rows="3" placeholder="Additional details..."></textarea>
            </div>

            <div class="grid-2-cols">
                <div class="form-group">
                    <label for="start_time">Start Time</label>
                    <input type="time" id="start_time" name="start_time" value="08:00" required>
                </div>
                <div class="form-group">
                    <label for="end_time">End Time</label>
                    <input type="time" id="end_time" name="end_time" value="20:00" required>
                </div>
            </div>

            <div class="form-group date-selection-group">
                <label>Select Dates</label>
                <p class="hint-text">Choose how you want to select the proposed days.</p>
                
                <div class="mode-selector mb-4">
                    <button type="button" class="mode-btn active" data-mode="specific">Specific</button>
                    <button type="button" class="mode-btn" data-mode="presets">Quick Sets</button>
                    <button type="button" class="mode-btn" data-mode="range">From-To</button>
                </div>

                <!-- Specific Dates Mode -->
                <div id="mode-specific" class="date-mode-panel active">
                    <div class="date-picker-container">
                        <input type="date" id="dateInput">
                        <button type="button" id="addDateBtn" class="btn btn-secondary">Add Date</button>
                    </div>
                </div>

                <!-- Presets Mode -->
                <div id="mode-presets" class="date-mode-panel hidden">
                    <div class="presets-grid">
                        <button type="button" class="btn btn-secondary preset-btn" data-preset="this-week">This Week (Mon-Sun)</button>
                        <button type="button" class="btn btn-secondary preset-btn" data-preset="this-weekend">This Weekend (Fri-Sun)</button>
                        <button type="button" class="btn btn-secondary preset-btn" data-preset="next-week">Next Week (Mon-Sun)</button>
                    </div>
                </div>

                <!-- Range Mode -->
                <div id="mode-range" class="date-mode-panel hidden">
                    <div class="grid-2-cols">
                        <div>
                            <label class="hint-text">From</label>
                            <input type="date" id="rangeStart">
                        </div>
                        <div>
                            <label class="hint-text">To</label>
                            <input type="date" id="rangeEnd">
                        </div>
                    </div>
                    <button type="button" id="addRangeBtn" class="btn btn-secondary w-100 mt-4">Add Range</button>
                </div>

                <!-- Hidden input to store selected dates as JSON -->
                <input type="hidden" id="datesData" name="dates" value="[]" required>
                
                <div class="selected-header mt-4 hidden" id="selectedDatesHeader">
                    <label>Selected Days</label>
                    <button type="button" id="clearDatesBtn" class="clear-btn text-danger">Clear All</button>
                </div>
                <div id="selectedDatesList" class="selected-dates">
                    <!-- Date badges will appear here -->
                </div>
                <div id="dateError" class="error-text hidden">Please select at least one date.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-large w-100 mt-4">Create Calendar</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
    <script src="js/app.js"></script>
</body>
</html>
