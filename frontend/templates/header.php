<?php
/**
 * Layout Header Component
 * Contains the main app header, patient tabs, and patient header banner
 */
require_once __DIR__ . '/../includes/config.php';

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($pageTitle ?? 'Hyperspace'); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/epic-styles.css">
    <script src="/assets/js/epic-app.js" defer></script>
</head>
<body data-patient-id="<?php echo sanitize($patient['id'] ?? ''); ?>">
    <!-- Main Application Header (Red Bar) -->
    <header class="app-header">
        <div class="app-logo">Epic</div>
        
        <div class="app-toolbar">
            <button class="toolbar-btn" title="Pt Station">
                <span class="icon">🏥</span> Pt Station
            </button>
            <button class="toolbar-btn" title="Today's Pts">
                <span class="icon">📋</span> Today's Pts
            </button>
            <button class="toolbar-btn" title="Patient Lists">
                <span class="icon">📁</span> Patient Lists
            </button>
            <span class="toolbar-separator"></span>
            <button class="toolbar-btn" title="In Basket">
                <span class="icon">📥</span> In Basket
            </button>
            <button class="toolbar-btn" title="My SmartPhrases">
                <span class="icon">📝</span> My SmartPhrases
            </button>
            <button class="toolbar-btn" title="Schedule">
                <span class="icon">📅</span> Schedule
            </button>
        </div>
        
        <div class="app-user-info">
            <span><?php echo sanitize($user['display_name'] ?? 'Unknown User'); ?></span>
            <span class="toolbar-separator"></span>
            <button class="toolbar-btn" title="Settings">⚙️</button>
            <button class="toolbar-btn" title="Log Out">🚪 Log Out</button>
        </div>
    </header>

    <!-- Patient Tab Bar -->
    <div class="patient-tabs">
        <?php if (isset($patient)): ?>
        <div class="patient-tab active" data-patient-id="<?php echo $patient['id']; ?>">
            <?php echo sanitize($patient['full_name'] ?? 'Unknown Patient'); ?>
            <span class="close-btn">×</span>
        </div>
        <?php endif; ?>
    </div>

    <?php if (isset($patient)): ?>
    <!-- Patient Header Banner -->
    <div class="patient-header">
        <!-- Patient Demographics Column -->
        <div class="patient-demographics">
            <div class="patient-name"><?php echo sanitize($patient['full_name']); ?></div>
            <div class="patient-info-row">
                <span><?php echo $patient['age']; ?> y.o.</span>
                <span>/</span>
                <span><?php echo sanitize($patient['gender']); ?></span>
                <span>/</span>
                <span><?php echo sanitize($patient['date_of_birth']); ?></span>
            </div>
            <div class="patient-info-row">
                <span><span class="label">CSN:</span> <?php echo sanitize($patient['csn'] ?? 'N/A'); ?></span>
            </div>
            
            <!-- Allergy Banner -->
            <?php 
            $allergyClass = 'unknown';
            $allergyText = 'Unknown: Not on File';
            if (!empty($allergies)) {
                $allergyClass = '';
                $allergyText = implode(', ', array_column($allergies, 'allergen'));
            } elseif (isset($allergies) && empty($allergies)) {
                $allergyClass = 'no-known';
                $allergyText = 'No Known Allergies';
            }
            ?>
            <div class="allergy-banner <?php echo $allergyClass; ?>">
                <span>Allergies:</span> <?php echo sanitize($allergyText); ?>
            </div>
        </div>

        <!-- Patient Identifiers Column -->
        <div class="patient-identifiers">
            <div class="identifier-group">
                <span class="label">MRN</span>
                <span class="value"><?php echo sanitize($patient['mrn']); ?></span>
            </div>
            <div class="identifier-group">
                <span class="label">Weight/BMI</span>
                <span class="value"><?php echo $patient['weight_lbs'] ? $patient['weight_lbs'] . ' lbs' : 'None'; ?></span>
            </div>
            <div class="identifier-group">
                <span class="label">Patient FYIs</span>
                <span class="value">None</span>
            </div>
            
            <?php if (isset($encounter)): ?>
            <div class="identifier-group">
                <span class="label">Room/Bed</span>
                <span class="value"><?php echo sanitize($encounter['room'] ?? 'N/A'); ?><?php echo $encounter['bed'] ? '/' . $encounter['bed'] : ''; ?></span>
            </div>
            <div class="identifier-group">
                <span class="label">Readmit Risk</span>
                <span class="value"><?php echo $encounter['readmit_risk'] ?? 0; ?></span>
            </div>
            <div class="identifier-group">
                <span class="label">Research</span>
                <span class="value">None</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Encounter Info Column -->
        <?php if (isset($encounter)): ?>
        <div class="encounter-info">
            <div class="location"><?php echo sanitize($encounter['facility'] ?? 'Unknown Facility'); ?></div>
            <div>
                <span class="label">Admit:</span> 
                <?php echo sanitize($encounter['admission_date']); ?>
            </div>
            <div>
                <span class="label">Patient Class:</span> 
                <?php echo sanitize($encounter['patient_class'] ?? 'N/A'); ?>
            </div>
            <div>
                <span class="label">Attending:</span> 
                <?php echo sanitize($encounter['attending_provider'] ?? 'N/A'); ?>
            </div>
            <?php if (!empty($encounter['code_status'])): ?>
            <div>
                <span class="label">Code Status:</span> 
                <span class="badge badge-info"><?php echo sanitize($encounter['code_status']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
