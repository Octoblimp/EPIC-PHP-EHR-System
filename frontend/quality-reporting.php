<?php
/**
 * Openspace EHR - CMS Quality Reporting
 * MIPS/QPP Compliance and Quality Measures Tracking
 */
require_once 'includes/config.php';
require_once 'includes/api.php';

$page_title = 'Quality Reporting - ' . APP_NAME;

// Demo MIPS measures data
$quality_measures = [
    [
        'id' => 'CMS122v10',
        'name' => 'Diabetes: Hemoglobin A1c (HbA1c) Poor Control (>9%)',
        'category' => 'Quality',
        'numerator' => 12,
        'denominator' => 145,
        'performance' => 91.7,
        'benchmark' => 85.0,
        'status' => 'met'
    ],
    [
        'id' => 'CMS165v10',
        'name' => 'Controlling High Blood Pressure',
        'category' => 'Quality',
        'numerator' => 187,
        'denominator' => 210,
        'performance' => 89.0,
        'benchmark' => 82.0,
        'status' => 'met'
    ],
    [
        'id' => 'CMS138v10',
        'name' => 'Preventive Care: Tobacco Screening & Cessation',
        'category' => 'Quality',
        'numerator' => 520,
        'denominator' => 580,
        'performance' => 89.7,
        'benchmark' => 90.0,
        'status' => 'at-risk'
    ],
    [
        'id' => 'CMS50v10',
        'name' => 'Closing the Referral Loop: Receipt of Specialist Report',
        'category' => 'Quality',
        'numerator' => 45,
        'denominator' => 78,
        'performance' => 57.7,
        'benchmark' => 75.0,
        'status' => 'not-met'
    ],
    [
        'id' => 'PI-MIPS-1',
        'name' => 'e-Prescribing',
        'category' => 'Promoting Interoperability',
        'numerator' => 892,
        'denominator' => 950,
        'performance' => 93.9,
        'benchmark' => 80.0,
        'status' => 'met'
    ],
    [
        'id' => 'PI-MIPS-2',
        'name' => 'Provide Patients Electronic Access',
        'category' => 'Promoting Interoperability',
        'numerator' => 445,
        'denominator' => 580,
        'performance' => 76.7,
        'benchmark' => 80.0,
        'status' => 'at-risk'
    ],
];

$mips_categories = [
    ['name' => 'Quality', 'weight' => 30, 'score' => 87.5, 'max' => 100],
    ['name' => 'Promoting Interoperability', 'weight' => 25, 'score' => 85.3, 'max' => 100],
    ['name' => 'Improvement Activities', 'weight' => 15, 'score' => 40, 'max' => 40],
    ['name' => 'Cost', 'weight' => 30, 'score' => 0, 'max' => 0], // Calculated by CMS
];

$provider_scores = [
    ['name' => 'Dr. Wilson', 'quality' => 92, 'pi' => 88, 'ia' => 40, 'total' => 89],
    ['name' => 'Dr. Smith', 'quality' => 85, 'pi' => 90, 'ia' => 40, 'total' => 86],
    ['name' => 'Dr. Johnson', 'quality' => 78, 'pi' => 75, 'ia' => 30, 'total' => 74],
];

include 'includes/header.php';
?>

<style>
.quality-page {
    padding: 20px;
    max-width: 1600px;
    margin: 0 auto;
}

.quality-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.quality-header h1 {
    font-size: 24px;
    color: #1a4a5e;
    margin: 0;
}

.reporting-year {
    display: flex;
    align-items: center;
    gap: 10px;
}

.reporting-year select {
    padding: 8px 15px;
    border: 2px solid #1a4a5e;
    border-radius: 4px;
    font-size: 14px;
}

.mips-overview {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.mips-score-card {
    background: linear-gradient(135deg, #1a4a5e 0%, #2d6b7f 100%);
    border-radius: 12px;
    padding: 30px;
    color: white;
}

.mips-score-card h2 {
    margin: 0 0 20px;
    font-size: 18px;
    opacity: 0.9;
}

.score-display {
    display: flex;
    align-items: center;
    gap: 30px;
}

.big-score {
    font-size: 72px;
    font-weight: 700;
    line-height: 1;
}

.score-details {
    flex: 1;
}

.score-bar {
    height: 12px;
    background: rgba(255,255,255,0.2);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 10px;
}

.score-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #7dd87d);
    border-radius: 6px;
}

.score-legend {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    opacity: 0.8;
}

.payment-impact {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.2);
}

.payment-impact h3 {
    font-size: 14px;
    margin: 0 0 10px;
}

.impact-value {
    font-size: 24px;
    font-weight: 600;
}

.impact-value.positive { color: #7dd87d; }
.impact-value.negative { color: #ff8a8a; }

.category-breakdown {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.category-breakdown h2 {
    margin: 0 0 20px;
    font-size: 18px;
    color: #1a4a5e;
}

.category-item {
    margin-bottom: 20px;
}

.category-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.category-name {
    font-weight: 600;
    color: #333;
}

.category-score {
    font-weight: 600;
    color: #1a4a5e;
}

.category-bar {
    height: 8px;
    background: #e8e8e8;
    border-radius: 4px;
    overflow: hidden;
}

.category-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.category-fill.quality { background: #17a2b8; }
.category-fill.pi { background: #28a745; }
.category-fill.ia { background: #ffc107; }
.category-fill.cost { background: #6c757d; }

.category-meta {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #888;
    margin-top: 4px;
}

.measures-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.measure-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #ddd;
}

.measure-card.met { border-left-color: #28a745; }
.measure-card.at-risk { border-left-color: #ffc107; }
.measure-card.not-met { border-left-color: #dc3545; }

.measure-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.measure-id {
    font-size: 12px;
    font-weight: 600;
    color: #1a4a5e;
    background: #e8eef2;
    padding: 3px 8px;
    border-radius: 4px;
}

.measure-status {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 12px;
}

.measure-status.met { background: #d4edda; color: #155724; }
.measure-status.at-risk { background: #fff3cd; color: #856404; }
.measure-status.not-met { background: #f8d7da; color: #721c24; }

.measure-name {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
    line-height: 1.4;
}

.measure-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.measure-stat {
    flex: 1;
}

.measure-stat-label {
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
}

.measure-stat-value {
    font-size: 18px;
    font-weight: 700;
    color: #1a4a5e;
}

.measure-progress {
    height: 6px;
    background: #e8e8e8;
    border-radius: 3px;
    overflow: hidden;
}

.measure-progress-fill {
    height: 100%;
    border-radius: 3px;
}

.measure-progress-fill.met { background: #28a745; }
.measure-progress-fill.at-risk { background: #ffc107; }
.measure-progress-fill.not-met { background: #dc3545; }

.measure-benchmark {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #888;
    margin-top: 5px;
}

.provider-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.provider-table h2 {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 15px 20px;
    margin: 0;
    font-size: 16px;
}

.provider-table table {
    width: 100%;
    border-collapse: collapse;
}

.provider-table th {
    background: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    border-bottom: 2px solid #e0e0e0;
}

.provider-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}

.provider-table tr:hover {
    background: #f8f9fa;
}

.score-badge {
    display: inline-block;
    width: 40px;
    text-align: center;
    padding: 4px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 12px;
}

.score-badge.high { background: #d4edda; color: #155724; }
.score-badge.medium { background: #fff3cd; color: #856404; }
.score-badge.low { background: #f8d7da; color: #721c24; }

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 30px 0 15px;
}

.section-header h2 {
    font-size: 18px;
    color: #1a4a5e;
    margin: 0;
}

.filter-tabs {
    display: flex;
    gap: 5px;
}

.filter-tab {
    padding: 6px 15px;
    background: #e8e8e8;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.filter-tab.active {
    background: #1a4a5e;
    color: white;
}
</style>

<div class="dashboard-content">
    <div class="quality-page">
        <div class="quality-header">
            <h1><i class="fas fa-chart-line"></i> CMS Quality Reporting (MIPS/QPP)</h1>
            <div class="reporting-year">
                <label>Reporting Year:</label>
                <select>
                    <option>2025</option>
                    <option>2024</option>
                    <option>2023</option>
                </select>
                <button class="btn btn-primary"><i class="fas fa-file-export"></i> Generate Report</button>
            </div>
        </div>
        
        <div class="mips-overview">
            <div class="mips-score-card">
                <h2><i class="fas fa-trophy"></i> Estimated MIPS Final Score</h2>
                <div class="score-display">
                    <div class="big-score">85.4</div>
                    <div class="score-details">
                        <div class="score-bar">
                            <div class="score-fill" style="width: 85.4%"></div>
                        </div>
                        <div class="score-legend">
                            <span>0</span>
                            <span>Performance Threshold: 75</span>
                            <span>100</span>
                        </div>
                    </div>
                </div>
                <div class="payment-impact">
                    <h3>Estimated Payment Adjustment (2027)</h3>
                    <div class="impact-value positive">+1.42%</div>
                    <small>Based on current performance trajectory</small>
                </div>
            </div>
            
            <div class="category-breakdown">
                <h2><i class="fas fa-chart-pie"></i> Category Performance</h2>
                <?php foreach ($mips_categories as $i => $cat): 
                    $fillClass = ['quality', 'pi', 'ia', 'cost'][$i];
                    $percent = $cat['max'] > 0 ? ($cat['score'] / $cat['max']) * 100 : 0;
                ?>
                <div class="category-item">
                    <div class="category-header">
                        <span class="category-name"><?php echo $cat['name']; ?></span>
                        <span class="category-score"><?php echo $cat['score']; ?>/<?php echo $cat['max']; ?></span>
                    </div>
                    <div class="category-bar">
                        <div class="category-fill <?php echo $fillClass; ?>" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                    <div class="category-meta">
                        <span>Weight: <?php echo $cat['weight']; ?>%</span>
                        <span>
                            <?php if ($cat['name'] === 'Cost'): ?>
                                Calculated by CMS
                            <?php else: ?>
                                <?php echo round($cat['score'] / $cat['max'] * 100); ?>% of maximum
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="section-header">
            <h2><i class="fas fa-clipboard-check"></i> Quality Measures Performance</h2>
            <div class="filter-tabs">
                <button class="filter-tab active">All Measures</button>
                <button class="filter-tab">Quality</button>
                <button class="filter-tab">Promoting Interoperability</button>
                <button class="filter-tab">At Risk</button>
            </div>
        </div>
        
        <div class="measures-grid">
            <?php foreach ($quality_measures as $measure): ?>
            <div class="measure-card <?php echo $measure['status']; ?>">
                <div class="measure-header">
                    <span class="measure-id"><?php echo $measure['id']; ?></span>
                    <span class="measure-status <?php echo $measure['status']; ?>">
                        <?php echo $measure['status'] === 'met' ? 'Met' : ($measure['status'] === 'at-risk' ? 'At Risk' : 'Not Met'); ?>
                    </span>
                </div>
                <div class="measure-name"><?php echo $measure['name']; ?></div>
                <div class="measure-stats">
                    <div class="measure-stat">
                        <div class="measure-stat-label">Performance</div>
                        <div class="measure-stat-value"><?php echo $measure['performance']; ?>%</div>
                    </div>
                    <div class="measure-stat">
                        <div class="measure-stat-label">Numerator</div>
                        <div class="measure-stat-value"><?php echo $measure['numerator']; ?></div>
                    </div>
                    <div class="measure-stat">
                        <div class="measure-stat-label">Denominator</div>
                        <div class="measure-stat-value"><?php echo $measure['denominator']; ?></div>
                    </div>
                </div>
                <div class="measure-progress">
                    <div class="measure-progress-fill <?php echo $measure['status']; ?>" style="width: <?php echo min($measure['performance'], 100); ?>%"></div>
                </div>
                <div class="measure-benchmark">
                    <span>Benchmark: <?php echo $measure['benchmark']; ?>%</span>
                    <span><?php echo $measure['performance'] >= $measure['benchmark'] ? '✓ Above benchmark' : '⚠ Below benchmark'; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="provider-table">
            <h2><i class="fas fa-user-md"></i> Provider Performance Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Quality (30%)</th>
                        <th>Promoting Interoperability (25%)</th>
                        <th>Improvement Activities (15%)</th>
                        <th>Estimated Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($provider_scores as $provider): 
                        $scoreClass = $provider['total'] >= 85 ? 'high' : ($provider['total'] >= 75 ? 'medium' : 'low');
                    ?>
                    <tr>
                        <td><strong><?php echo $provider['name']; ?></strong></td>
                        <td><span class="score-badge <?php echo $provider['quality'] >= 85 ? 'high' : ($provider['quality'] >= 75 ? 'medium' : 'low'); ?>"><?php echo $provider['quality']; ?></span></td>
                        <td><span class="score-badge <?php echo $provider['pi'] >= 85 ? 'high' : ($provider['pi'] >= 75 ? 'medium' : 'low'); ?>"><?php echo $provider['pi']; ?></span></td>
                        <td><span class="score-badge <?php echo $provider['ia'] >= 35 ? 'high' : ($provider['ia'] >= 20 ? 'medium' : 'low'); ?>"><?php echo $provider['ia']; ?></span></td>
                        <td><strong><?php echo $provider['total']; ?></strong></td>
                        <td>
                            <?php if ($provider['total'] >= 75): ?>
                                <span style="color: #28a745;"><i class="fas fa-check-circle"></i> Above Threshold</span>
                            <?php else: ?>
                                <span style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> Below Threshold</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section-header" style="margin-top: 30px;">
            <h2><i class="fas fa-lightbulb"></i> Improvement Recommendations</h2>
        </div>
        
        <div class="measures-grid">
            <div class="measure-card at-risk">
                <div class="measure-name"><i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i> Tobacco Screening Below Target</div>
                <p style="font-size: 13px; color: #666; margin: 10px 0;">Current performance: 89.7% (Target: 90%)</p>
                <p style="font-size: 13px;"><strong>Action:</strong> Review 58 patients in denominator without documented screening. Add tobacco screening to nursing intake workflow.</p>
            </div>
            <div class="measure-card not-met">
                <div class="measure-name"><i class="fas fa-times-circle" style="color: #dc3545;"></i> Referral Loop Closure Critical</div>
                <p style="font-size: 13px; color: #666; margin: 10px 0;">Current performance: 57.7% (Target: 75%)</p>
                <p style="font-size: 13px;"><strong>Action:</strong> 33 pending specialist reports. Contact specialists for follow-up. Consider Direct messaging for faster report receipt.</p>
            </div>
            <div class="measure-card at-risk">
                <div class="measure-name"><i class="fas fa-user-friends" style="color: #ffc107;"></i> Patient Portal Access</div>
                <p style="font-size: 13px; color: #666; margin: 10px 0;">Current performance: 76.7% (Target: 80%)</p>
                <p style="font-size: 13px;"><strong>Action:</strong> 135 patients eligible but not enrolled. Front desk should offer enrollment at check-in. Send portal invitation emails.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        // Demo filter logic would go here
    });
});
</script>

<?php include 'includes/footer.php'; ?>
