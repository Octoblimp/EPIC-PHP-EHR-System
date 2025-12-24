<?php
/**
 * Flowsheets Activity View
 * Main flowsheet documentation interface
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/api.php';

// Get patient ID
$patientId = $_GET['patient_id'] ?? 1;

// Get flowsheet group
$flowsheetGroup = $_GET['group'] ?? 'Pastoral Services';

// Fetch patient data
$headerData = $patientService->getHeader($patientId);
$patient = $headerData['success'] ? $headerData['data']['patient'] : null;
$encounter = $headerData['success'] ? $headerData['data']['encounter'] : null;
$allergies = $headerData['success'] ? $headerData['data']['allergies'] : [];

// Fetch flowsheet data
$flowsheetGroups = $flowsheetService->getGroups();
$flowsheetData = $flowsheetService->getGrouped($patientId, $flowsheetGroup);

$pageTitle = 'Flowsheets';
$currentActivity = 'flowsheets';

// Include header
include __DIR__ . '/../templates/header.php';
?>

    <!-- Main Content Container -->
    <div class="main-container">
        <!-- Navigation Sidebar -->
        <?php include __DIR__ . '/../templates/navigation.php'; ?>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Activity Header / Toolbar -->
            <div class="content-header">
                <span class="content-title">Flowsheets</span>
                <div class="content-toolbar">
                    <button class="content-btn" onclick="window.location.href='flowsheets.php?patient_id=<?php echo $patientId; ?>&group=Pastoral Services'">
                        <span class="icon">📋</span> File
                    </button>
                    <button class="content-btn">
                        <span class="icon">➕</span> Add Rows
                    </button>
                    <button class="content-btn">
                        <span class="icon">➕</span> Add LDA
                    </button>
                    <button class="content-btn">
                        <span class="icon">🔗</span> Cascade
                    </button>
                    <span class="toolbar-separator" style="height: 20px; width: 1px; background: #ccc;"></span>
                    <button class="content-btn">
                        <span class="icon">➕</span> Add Col
                    </button>
                    <button class="content-btn">
                        <span class="icon">📥</span> Insert Col
                    </button>
                    <button class="content-btn">
                        <span class="icon">⏮️</span> Last Filed
                    </button>
                    <button class="content-btn" style="background: #ffd700;">
                        <span class="icon">📄</span> Req Doc
                    </button>
                    <button class="content-btn">
                        <span class="icon">📈</span> Graph
                    </button>
                    <button class="content-btn">
                        <span class="icon">📅</span> Go to Date
                    </button>
                    <button class="content-btn">
                        <span class="icon">👤</span> Responsible
                    </button>
                    <button class="content-btn">
                        <span class="icon">🔄</span> Refresh
                    </button>
                    <button class="content-btn">
                        <span class="icon">ℹ️</span> Legend
                    </button>
                    <button class="content-btn">
                        <span class="icon">⚙️</span> Cosign
                    </button>
                    <button class="content-btn">
                        <span class="icon">📏</span> Ling Lines
                    </button>
                </div>
            </div>

            <!-- Flowsheet Tabs -->
            <div class="flowsheet-tabs">
                <?php 
                $groups = $flowsheetGroups['success'] ? $flowsheetGroups['data'] : [];
                foreach ($groups as $group): 
                    $isActive = $group['name'] === $flowsheetGroup;
                ?>
                <div class="flowsheet-tab <?php echo $isActive ? 'active' : ''; ?>" 
                     onclick="window.location.href='flowsheets.php?patient_id=<?php echo $patientId; ?>&group=<?php echo urlencode($group['name']); ?>'">
                    <?php echo sanitize($group['name']); ?>
                </div>
                <?php endforeach; ?>
                <div class="flowsheet-tab">General Information</div>
                <div class="flowsheet-tab">Expiration Checklist</div>
            </div>

            <!-- Flowsheet Options Bar -->
            <div class="flowsheet-options">
                <span class="flowsheet-checkbox">
                    <input type="checkbox" id="hideAll">
                    <label for="hideAll">Hide All</label>
                </span>
                <span class="flowsheet-checkbox">
                    <input type="checkbox" id="showAll" checked>
                    <label for="showAll">Show All</label>
                </span>
                <span class="toolbar-separator" style="height: 16px; width: 1px; background: #ccc; margin: 0 10px;"></span>
                <span>Accordion</span>
                <span>Expanded</span>
                <button class="content-btn" style="padding: 2px 8px;">View All</button>
                
                <div class="flowsheet-view-toggle">
                    <button class="content-btn">Reset</button>
                    <button class="content-btn" style="background: #4dabf7; color: white;">Now</button>
                </div>
            </div>

            <!-- Main Flowsheet Content -->
            <div class="content-body" style="display: flex; padding: 0;">
                <!-- Section Checkboxes (Left) -->
                <div class="flowsheet-section-list" style="width: 200px; border-right: 1px solid #ccc;">
                    <?php 
                    // Get current group sections
                    $currentGroupData = null;
                    foreach ($groups as $g) {
                        if ($g['name'] === $flowsheetGroup) {
                            $currentGroupData = $g;
                            break;
                        }
                    }
                    
                    if ($currentGroupData && isset($currentGroupData['sections'])):
                        foreach ($currentGroupData['sections'] as $section):
                    ?>
                    <div class="section-checkbox">
                        <input type="checkbox" id="section_<?php echo md5($section); ?>" checked>
                        <label for="section_<?php echo md5($section); ?>"><?php echo sanitize($section); ?></label>
                    </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>

                <!-- Flowsheet Grid (Center) -->
                <div id="flowsheet-container" class="flowsheet-container" style="flex: 1;">
                    <?php 
                    $data = $flowsheetData['success'] ? $flowsheetData['data'] : [];
                    if (!empty($data)):
                    ?>
                    <div class="flowsheet-grid">
                        <!-- Row Headers -->
                        <div class="flowsheet-rows">
                            <?php foreach ($data as $section => $rows): ?>
                                <div class="flowsheet-row-header section"><?php echo sanitize($section); ?></div>
                                <?php foreach ($rows as $rowName => $entries): ?>
                                    <div class="flowsheet-row-header row"><?php echo sanitize($rowName); ?></div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Data Columns -->
                        <div class="flowsheet-data">
                            <?php
                            // Collect all unique timestamps
                            $timestamps = [];
                            foreach ($data as $section => $rows) {
                                foreach ($rows as $rowName => $entries) {
                                    foreach ($entries as $entry) {
                                        $timestamps[$entry['entry_datetime']] = true;
                                    }
                                }
                            }
                            ksort($timestamps);
                            $timestamps = array_keys($timestamps);
                            ?>
                            
                            <!-- Time Header -->
                            <div class="flowsheet-time-header">
                                <?php 
                                // Add facility/date header
                                if (!empty($encounter)):
                                ?>
                                <div class="flowsheet-time-cell" style="min-width: 150px;">
                                    <div class="date"><?php echo sanitize($encounter['facility'] ?? ''); ?></div>
                                    <div class="time"><?php echo date('n/j/y'); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php foreach ($timestamps as $ts): ?>
                                <div class="flowsheet-time-cell highlight">
                                    <div class="time" style="font-weight: bold; font-size: 14px;">
                                        <?php 
                                        $parts = explode(' ', $ts);
                                        echo isset($parts[1]) ? substr(str_replace(':', '', $parts[1]), 0, 4) : '';
                                        ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Values -->
                            <div class="flowsheet-values">
                                <?php foreach ($data as $section => $rows): ?>
                                    <!-- Section row (empty) -->
                                    <div class="flowsheet-value-row" style="background: var(--flowsheet-header-bg); height: 28px;">
                                        <?php foreach ($timestamps as $ts): ?>
                                        <div class="flowsheet-value-cell"></div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <?php foreach ($rows as $rowName => $entries): ?>
                                    <div class="flowsheet-value-row">
                                        <?php if (!empty($encounter)): ?>
                                        <div class="flowsheet-value-cell" style="min-width: 150px;"></div>
                                        <?php endif; ?>
                                        
                                        <?php foreach ($timestamps as $ts): 
                                            $entry = null;
                                            foreach ($entries as $e) {
                                                if ($e['entry_datetime'] === $ts) {
                                                    $entry = $e;
                                                    break;
                                                }
                                            }
                                        ?>
                                        <div class="flowsheet-value-cell <?php echo $entry ? '' : 'editable'; ?>"
                                             data-section="<?php echo sanitize($section); ?>"
                                             data-row="<?php echo sanitize($rowName); ?>"
                                             data-timestamp="<?php echo sanitize($ts); ?>">
                                            <?php echo $entry ? sanitize($entry['value']) : ''; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="p-3 text-center text-muted">
                        No flowsheet data found for this patient in <?php echo sanitize($flowsheetGroup); ?>.
                        <br><br>
                        <button class="btn btn-primary" onclick="addNewColumn()">Add Column</button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Selection Panel (Right) - for follow-up options like in Epic -->
                <div style="width: 280px; background: #f8f8f8; border-left: 1px solid #ccc;">
                    <div class="panel-header" style="background: #6c757d;">
                        <?php echo sanitize($flowsheetGroup); ?>
                    </div>
                    
                    <div class="p-2">
                        <div class="text-small text-muted mb-1">
                            <?php echo date('m/d/y'); ?> <?php echo date('Hi'); ?>
                        </div>
                        <div class="text-bold mb-2">
                            <?php echo sanitize($flowsheetGroup); ?> Follow-up
                        </div>
                        
                        <div class="text-small text-muted mb-1">Select Multiple Options (F5)</div>
                        
                        <div class="selection-list" style="max-height: 400px;">
                            <?php
                            $followUpOptions = [
                                'Contact with family but not with patient',
                                'Contact with patient but not family',
                                'Cultural/belief dynamics counter to medical treatment',
                                'Discussed end-of-life issues',
                                'Ethics consult requested',
                                'Family dynamics negatively affecting patient welfare',
                                'Family conference initiated',
                                'No needs identified at this time',
                                'No needs expressed at this time',
                                'Organ/tissue conversation initiated',
                                'Patient requests additional prayer',
                                'Prayer provided',
                                'Provided relaxation/meditation/visual imagery'
                            ];
                            foreach ($followUpOptions as $option):
                            ?>
                            <div class="selection-item"><?php echo sanitize($option); ?></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-2">
                            <label class="text-small">Comment (F6)</label>
                            <textarea class="form-control" rows="3" placeholder="Enter comments..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <?php include __DIR__ . '/../templates/right-panel.php'; ?>
    </div>

    <script>
        // Initialize flowsheet manager
        const flowsheetManager = new FlowsheetManager('flowsheet-container', <?php echo $patientId; ?>);
        
        // Selection item click handler
        document.querySelectorAll('.selection-item').forEach(item => {
            item.addEventListener('click', function() {
                this.classList.toggle('selected');
            });
        });
        
        function addNewColumn() {
            const timestamp = prompt('Enter date/time (YYYY-MM-DD HH:MM):', new Date().toISOString().slice(0, 16).replace('T', ' '));
            if (timestamp) {
                // Would refresh the flowsheet with new column
                window.location.reload();
            }
        }
    </script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
