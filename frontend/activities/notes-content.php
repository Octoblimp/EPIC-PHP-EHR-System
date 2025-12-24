<?php
/**
 * Notes Tab Content
 */
?>
<div class="content-panel">
    <div class="panel-header gray">
        <span><i class="fas fa-sticky-note"></i> Clinical Notes</span>
        <div class="panel-header-actions">
            <a href="#">+ New Note</a>
            <a href="#">Filter</a>
        </div>
    </div>
    <div class="panel-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Note Type</th>
                    <th>Author</th>
                    <th>Date/Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><a href="#"><strong>Progress Note</strong></a></td>
                    <td>Dr. Wilson, Sarah MD</td>
                    <td>Today 07:30</td>
                    <td><span class="text-success">Signed</span></td>
                    <td><a href="#">View</a></td>
                </tr>
                <tr>
                    <td><a href="#"><strong>Nursing Assessment Note</strong></a></td>
                    <td>Jones, Sarah RN</td>
                    <td>Today 06:00</td>
                    <td><span class="text-success">Signed</span></td>
                    <td><a href="#">View</a></td>
                </tr>
                <tr>
                    <td><a href="#"><strong>Pharmacy Consult Note</strong></a></td>
                    <td>PharmD Chen, Michael</td>
                    <td>Yesterday 14:00</td>
                    <td><span class="text-success">Signed</span></td>
                    <td><a href="#">View</a></td>
                </tr>
                <tr>
                    <td><a href="#"><strong>H&P (History and Physical)</strong></a></td>
                    <td>Dr. Wilson, Sarah MD</td>
                    <td>Yesterday 09:00</td>
                    <td><span class="text-success">Signed</span></td>
                    <td><a href="#">View</a></td>
                </tr>
                <tr>
                    <td><a href="#"><strong>Admission Note</strong></a></td>
                    <td>Dr. Wilson, Sarah MD</td>
                    <td>2 days ago</td>
                    <td><span class="text-success">Signed</span></td>
                    <td><a href="#">View</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Note Preview -->
<div class="content-panel mt-3">
    <div class="panel-header blue">
        <span><i class="fas fa-file-medical-alt"></i> Progress Note - Today 07:30</span>
        <div class="panel-header-actions">
            <a href="#">Print</a>
            <a href="#">Addendum</a>
        </div>
    </div>
    <div class="panel-content">
        <div style="font-size: 12px; line-height: 1.6;">
            <p><strong>Author:</strong> Wilson, Sarah MD | <strong>Signed:</strong> Today 07:35</p>
            <hr style="border: none; border-top: 1px solid #ddd; margin: 10px 0;">
            
            <p><strong>SUBJECTIVE:</strong></p>
            <p style="margin-left: 20px;">
                68 y.o. male admitted for community-acquired pneumonia and management of chronic conditions.
                Patient reports improvement in dyspnea overnight. Cough productive with white sputum, less frequent.
                Denies chest pain, fever, or chills. Pain controlled at 3/10 with current regimen.
                Slept well overnight. Good appetite for breakfast.
            </p>
            
            <p><strong>OBJECTIVE:</strong></p>
            <div style="margin-left: 20px;">
                <p><strong>Vitals:</strong> T 98.6°F, BP 158/92 (elevated), HR 88 irregular, RR 18, SpO2 96% RA</p>
                <p><strong>General:</strong> Alert, oriented, NAD, conversant</p>
                <p><strong>HEENT:</strong> NCAT, PERRL, MMM, OP clear</p>
                <p><strong>CV:</strong> Irregularly irregular, no murmur, no edema</p>
                <p><strong>Pulm:</strong> Clear to auscultation bilaterally, improved from admission. No wheezes/rales.</p>
                <p><strong>Abd:</strong> Soft, NT, ND, +BS</p>
                <p><strong>Neuro:</strong> A&O x4, CN II-XII intact</p>
            </div>
            
            <p><strong>LABS:</strong></p>
            <p style="margin-left: 20px;">
                WBC 12.5 (↓ from 15.2), Hgb 11.2, Plt 225<br>
                Na 138, K 4.2, BUN 28, Cr 1.8 (stable), Glucose 186 (elevated)<br>
                Blood cultures pending (24 hrs)
            </p>
            
            <p><strong>ASSESSMENT/PLAN:</strong></p>
            <div style="margin-left: 20px;">
                <p>1. <strong>Community-acquired pneumonia</strong> - Improving on Vancomycin. WBC trending down. Continue current antibiotics. Await blood cultures.</p>
                <p>2. <strong>Type 2 DM</strong> - Glucose elevated. Continue home regimen. Add sliding scale coverage. A1C pending.</p>
                <p>3. <strong>HTN</strong> - Elevated today. Continue Lisinopril and Metoprolol. Monitor.</p>
                <p>4. <strong>CKD Stage 3</strong> - Creatinine stable. Avoid nephrotoxins.</p>
                <p>5. <strong>AFib on anticoagulation</strong> - Continue Eliquis. Rate controlled.</p>
            </div>
            
            <p><strong>Disposition:</strong> Continue inpatient. Anticipate discharge in 2-3 days if continues to improve.</p>
        </div>
    </div>
</div>
