<?php
/** @var array $teacher */
/** @var string $mode */
/** @var string $title */

$base = \App\Core\App::getInstance()->getBasePath();

// Helper for select matching
function isSelected($val1, $val2) {
    return (string)$val1 === (string)$val2 ? 'selected' : '';
}
function isChecked($val1, $val2) {
    return (string)$val1 === (string)$val2 ? 'checked' : '';
}
?>

<div class="top-bar">
    <h1 id="page-title"><?= $title ?></h1>
    <div style="display: flex; gap: 1rem;">
        <button class="btn" onclick="history.back()" style="background: var(--glass-bg); color: var(--text-main);">취소</button>
        <button class="btn btn-primary" onclick="document.getElementById('teacherForm').submit()">저장하기</button>
    </div>
</div>

<div class="toast-container" id="toast-container"></div>

<form id="teacherForm" action="<?= $base ?>index.php?action=save_teacher" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="<?= $mode ?>">
    <input type="hidden" name="login_id" value="<?= $teacher['login_id'] ?? '' ?>">

    <div class="dashboard-grid" style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
        
        <!-- Left Column: Photo & Profile Summary -->
        <aside>
            <div class="glass-card" style="text-align: center; padding: 2rem; position: sticky; top: 2rem;">
                <div id="photo-container" onclick="document.getElementById('photo-input').click()" style="width: 150px; height: 180px; background: var(--bg-dark); border-radius: 12px; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; border: 2px dashed var(--glass-border); overflow: hidden; cursor: pointer; position: relative;">
                    <?php if (!empty($teacher['photo_path'])): ?>
                        <img id="photo-preview" src="<?= $base ?><?= $teacher['photo_path'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div id="photo-placeholder">
                            <span style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;">📷</span>
                            <span style="color: var(--text-muted); font-size: 0.8rem;">사진 업로드</span>
                        </div>
                        <img id="photo-preview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                    <?php endif; ?>
                    <div class="photo-overlay" style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.6); color: white; font-size: 0.7rem; padding: 0.4rem; opacity: 0; transition: 0.3s; pointer-events: none;">클릭하여 변경</div>
                </div>
                <input type="file" id="photo-input" name="photo" accept="image/jpeg,image/png" style="display: none;" onchange="previewImage(this)">
                
                <h3 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($teacher['name'] ?? '신규 교사') ?></h3>
                <p style="color: var(--text-muted); font-size: 0.875rem;"><?= htmlspecialchars($teacher['login_id'] ?? '-') ?></p>
                
                <div style="margin-top: 2rem; text-align: left; font-size: 0.875rem; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">부서</span> 
                        <span style="color: var(--primary); font-weight: 600;">
                            <?= ['1'=>'초등', '2'=>'중고', '5'=>'초·중고', '3'=>'대건', '4'=>'장애'][$teacher['department'] ?? ''] ?? '-' ?>
                        </span>
                    </div>
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">근속</span> 
                        <span style="color: var(--accent); font-weight: 600;">
                            <?php
                                if (!empty($teacher['cs_year'])) {
                                    $start = new DateTime($teacher['cs_year'] . '-' . $teacher['cs_month'] . '-01');
                                    $diff = $start->diff(new DateTime());
                                    echo "{$diff->y}년 {$diff->m}개월";
                                } else { echo "기록 없음"; }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Right Column: Tabs/Forms -->
        <div class="glass-card" style="padding: 2rem;">
            <div style="display: flex; gap: 2rem; border-bottom: 1px solid var(--glass-border); margin-bottom: 2rem; overflow-x: auto;">
                <button type="button" class="tab-btn active" onclick="showTab('basic')">인적 사항</button>
                <button type="button" class="tab-btn" onclick="showTab('assignment')">소속 및 수상</button>
                <button type="button" class="tab-btn" onclick="showTab('edu_detail')">상세 교육</button>
                <button type="button" class="tab-btn" onclick="showTab('history')">참여 이력</button>
            </div>

            <!-- 1. 인적 사항 -->
            <div id="tab-basic" class="tab-content active">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label>이름</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($teacher['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>세례명</label>
                        <input type="text" name="bname" value="<?= htmlspecialchars($teacher['baptismal_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>생년월일 (YYYY-MM-DD)</label>
                        <input type="date" name="jumin_f" value="<?= htmlspecialchars($teacher['birth_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>영명축일 (MM/DD)</label>
                        <input type="text" name="bday" value="<?= htmlspecialchars($teacher['bday'] ?? '') ?>" placeholder="05/15">
                    </div>
                    <div class="form-group">
                        <label>휴대전화</label>
                        <input type="text" name="phone2" value="<?= htmlspecialchars($teacher['mobile_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>자택전화</label>
                        <input type="text" name="phone1" value="<?= htmlspecialchars($teacher['home_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>이메일</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($teacher['email'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>주소</label>
                        <div style="display: grid; grid-template-columns: 120px 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <input type="text" name="postcode" value="<?= htmlspecialchars($teacher['post_code'] ?? '') ?>" placeholder="우편번호">
                            <button type="button" class="btn" style="background: var(--bg-dark);">주소 찾기</button>
                        </div>
                        <input type="text" name="addr1" value="<?= htmlspecialchars($teacher['address_basic'] ?? '') ?>" placeholder="기본 주소" style="margin-bottom: 0.5rem;">
                        <input type="text" name="addr2" value="<?= htmlspecialchars($teacher['address_detail'] ?? '') ?>" placeholder="상세 주소">
                    </div>
                </div>
            </div>

            <div id="tab-assignment" class="tab-content" style="display: none;">
                <div style="display: flex; flex-direction: column; gap: 2.5rem;">
                    
                    <!-- 1. 소속 정보 -->
                    <section>
                        <h4 style="margin-bottom: 1.5rem; color: var(--primary); display: flex; align-items: center; gap: 0.75rem; font-size: 1.1rem;">
                            <span style="background: rgba(79, 70, 229, 0.1); padding: 0.5rem; border-radius: 8px;">🏢</span> 소속 정보
                        </h4>
                        <div class="glass-card" style="padding: 2rem; display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                            <div class="form-group">
                                <label>소속 본당</label>
                                <div style="display: grid; grid-template-columns: 1fr 100px; gap: 0.5rem;">
                                    <?php 
                                        $currentParishName = '';
                                        foreach ($parishes as $p) {
                                            if ((string)$p['id'] === (string)($teacher['parish_id'] ?? '')) {
                                                $currentParishName = "[" . ($p['diocese_name'] ?? '') . "] " . $p['parish_name'];
                                                break;
                                            }
                                        }
                                    ?>
                                    <input type="text" id="parish_name_display" value="<?= htmlspecialchars($currentParishName) ?>" readonly placeholder="본당을 검색하세요" style="background: var(--bg-dark); cursor: default;">
                                    <input type="hidden" name="parish_id" id="parish_id_input" value="<?= htmlspecialchars((string)($teacher['parish_id'] ?? '')) ?>">
                                    <button type="button" class="btn" onclick="openParishModal()" style="background: var(--primary); color: white;">검색</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>부서</label>
                                <select name="academy">
                                    <option value="1" <?= isSelected($teacher['department'] ?? '', 'elementary') ?>>초등부</option>
                                    <option value="2" <?= isSelected($teacher['department'] ?? '', 'middle_high') ?>>중고등부</option>
                                    <option value="5" <?= isSelected($teacher['department'] ?? '', 'daegun') ?>>대건</option>
                                    <option value="3" <?= isSelected($teacher['department'] ?? '', 'disabled') ?>>장애아</option>
                                    <option value="4" <?= isSelected($teacher['department'] ?? '', 'integrated') ?>>초·중고 통합</option>
                                </select>
                            </div>
                        </div>
                        <div class="glass-card" style="padding: 2rem; margin-top: 1.5rem; display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                            <div class="form-group">
                                <label>직급</label>
                                <input type="text" name="position" value="<?= htmlspecialchars($teacher['position'] ?? '') ?>" placeholder="예: 평교사">
                            </div>
                            <div class="form-group">
                                <label>근속 기준 연월</label>
                                <div style="display: flex; gap: 0.75rem;">
                                    <div style="flex: 1; position: relative;">
                                        <input type="text" name="cs_year" value="<?= htmlspecialchars($teacher['cs_year'] ?? '') ?>" placeholder="YYYY" style="text-align: center;">
                                        <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 0.7rem; color: var(--text-muted);">년</span>
                                    </div>
                                    <div style="flex: 1; position: relative;">
                                        <input type="text" name="cs_month" value="<?= htmlspecialchars($teacher['cs_month'] ?? '') ?>" placeholder="MM" style="text-align: center;">
                                        <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 0.7rem; color: var(--text-muted);">월</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="glass-card" style="padding: 1.5rem; margin-top: 1.5rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>비고 / 기타사항 (휴직사유 등)</label>
                                <textarea name="ac_edsc" rows="2" style="width: 100%; background: var(--bg-dark); color: var(--text-main); border: 1px solid var(--glass-border); border-radius: 8px; padding: 0.75rem; font-family: inherit; font-size: 0.875rem;"><?= htmlspecialchars($teacher['current_grade'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- 2. 휴직 이력 -->
                    <section>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h4 style="color: var(--danger); display: flex; align-items: center; gap: 0.75rem; font-size: 1.1rem;">
                                <span style="background: rgba(239, 68, 68, 0.1); padding: 0.5rem; border-radius: 8px;">⏸️</span> 휴직 이력
                            </h4>
                            <button type="button" class="btn" onclick="addFurlough()" style="padding: 0.5rem 1rem; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2);">
                                + 휴직 추가
                            </button>
                        </div>
                        <div class="glass-card" style="padding: 1rem;">
                            <div id="furloughs-container" style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php 
                                    $furloughs = $teacher['furloughs'] ?? [];
                                    foreach ($furloughs as $f): 
                                ?>
                                <div class="furlough-item" style="display: grid; grid-template-columns: 200px 1fr 40px; gap: 1.5rem; align-items: center; background: rgba(0,0,0,0.1); padding: 1rem; border-radius: 12px; border: 1px solid var(--glass-border);">
                                    <select name="furlough_reason[]">
                                        <option value="0">사유 선택</option>
                                        <option value="1" <?= isSelected($f['reason'] ?? '', '1') ?>>군복무</option>
                                        <option value="2" <?= isSelected($f['reason'] ?? '', '2') ?>>임신출산</option>
                                        <option value="3" <?= isSelected($f['reason'] ?? '', '3') ?>>병환</option>
                                        <option value="4" <?= isSelected($f['reason'] ?? '', '4') ?>>유학/연수</option>
                                        <option value="5" <?= isSelected($f['reason'] ?? '', '5') ?>>거주지이동</option>
                                    </select>
                                    <div style="display: flex; gap: 1rem; align-items: center;">
                                        <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">시작</span>
                                            <input type="date" name="furlough_start[]" value="<?= $f['start_date'] ?? '' ?>">
                                        </div>
                                        <span style="color: var(--text-muted);">~</span>
                                        <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">종료</span>
                                            <input type="date" name="furlough_end[]" value="<?= $f['end_date'] ?? '' ?>">
                                        </div>
                                    </div>
                                    <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.5rem;">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>

                    <!-- 3. 수상 내역 -->
                    <section>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h4 style="color: var(--accent); display: flex; align-items: center; gap: 0.75rem; font-size: 1.1rem;">
                                <span style="background: rgba(14, 165, 233, 0.1); padding: 0.5rem; border-radius: 8px;">🏅</span> 근속상 수상 내역
                            </h4>
                            <button type="button" class="btn" onclick="addAward()" style="padding: 0.5rem 1rem; font-size: 0.8rem; background: rgba(14, 165, 233, 0.1); color: var(--accent); border: 1px solid rgba(14, 165, 233, 0.2);">
                                + 수상 추가
                            </button>
                        </div>
                        <div class="glass-card" style="padding: 1.5rem;">
                            <div id="awards-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <?php 
                                    $awards = $teacher['awards'] ?? [];
                                    foreach ($awards as $award): 
                                ?>
                                <div class="award-item" style="display: flex; gap: 0.75rem; align-items: center; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 10px; border: 1px solid var(--glass-border);">
                                    <div style="width: 100px; position: relative;">
                                        <input type="text" name="award_year[]" value="<?= htmlspecialchars($award['tml_year'] ?? '') ?>" placeholder="연도" style="text-align: center; padding-right: 1.5rem;">
                                        <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-size: 0.65rem; color: var(--text-muted);">년</span>
                                    </div>
                                    <div style="flex: 1;">
                                        <select name="award_name[]">
                                            <option value="">수상 선택</option>
                                            <?php 
                                                $awardOptions = [3, 5, 10, 15, 20, 25];
                                                foreach($awardOptions as $y): 
                                            ?>
                                                <option value="<?= $y ?>" <?= isSelected($award['tml'] ?? '', (string)$y) ?>><?= $y ?>년 근속상</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.2rem; padding: 0 0.5rem;">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- 3. 상세 교육 -->
            <div id="tab-edu_detail" class="tab-content" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h4 style="color: var(--primary); display: flex; align-items: center; gap: 0.75rem; font-size: 1.1rem;">
                        <span style="background: rgba(79, 70, 229, 0.1); padding: 0.5rem; border-radius: 8px;">📚</span> 과목별 수료 현황
                    </h4>
                    <button type="button" class="btn" onclick="addEducation()" style="padding: 0.5rem 1rem; font-size: 0.8rem; background: rgba(79, 70, 229, 0.1); color: var(--primary); border: 1px solid rgba(79, 70, 229, 0.2);">
                        + 교육 추가
                    </button>
                </div>
                
                <div id="education-container" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php 
                        $eduDetails = $teacher['edu_details'] ?? [];
                        foreach ($eduDetails as $edu): 
                    ?>
                    <div class="edu-item glass-card" style="padding: 1rem; display: grid; grid-template-columns: 1fr 200px 40px; gap: 1.5rem; align-items: center;">
                        <div class="form-group" style="margin: 0;">
                            <input type="text" name="edu_title[]" value="<?= htmlspecialchars($edu['edu_title'] ?? '') ?>" placeholder="교육 과목명">
                        </div>
                        <div class="form-group" style="margin: 0; position: relative;">
                            <input type="text" name="edu_date[]" value="<?= htmlspecialchars($edu['edu_dt'] ?? '') ?>" placeholder="YYYY-MM-DD" style="text-align: center;">
                            <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 0.7rem; color: var(--text-muted);">수료일</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.5rem;">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. 참여 이력 -->
            <div id="tab-history" class="tab-content" style="display: none;">
                <h4 style="margin-bottom: 1.5rem; color: var(--primary);">🕒 교육 및 연수 참가 이력 (자동 집계)</h4>
                <div class="table-container">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                                <th style="padding: 1rem;">날짜</th>
                                <th style="padding: 1rem;">교육명</th>
                                <th style="padding: 1rem; text-align: center;">상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teacher['participation'] ?? [] as $p): ?>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 1rem; font-size: 0.85rem;">
                                    <?= substr($p['edu_date'], 0, 10) ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600;"><?= htmlspecialchars($p['edu_subject']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $p['edu_year'] ?>년 교육</div>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <span class="badge badge-active"><?= $p['att_ok'] === '1' ? '출석' : '결석' ?></span>
                                </td>
                            </tr>
                            <?php endforeach; if(empty($teacher['participation'])): ?>
                            <tr><td colspan="3" style="padding: 3rem; text-align: center; color: var(--text-muted);">참여 이력이 없습니다.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
    .tab-btn {
        background: none; border: none; padding: 1rem 0; color: var(--text-muted);
        font-weight: 600; cursor: pointer; transition: var(--transition);
        border-bottom: 2px solid transparent; white-space: nowrap;
    }
    .tab-btn.active { color: var(--primary); border-bottom: 2px solid var(--primary); }
    .tab-btn:hover { color: var(--text-main); }
    .badge { font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 20px; font-weight: 700; }
    .badge-active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .badge-upcoming { background: rgba(148, 163, 184, 0.2); color: #94a3b8; }
    
    .award-item, .furlough-item, .edu-item { transition: all 0.3s ease; }
    .award-item:hover, .furlough-item:hover, .edu-item:hover { transform: translateY(-2px); border-color: var(--primary) !important; }
</style>

<script>
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('tab-' + tabName).style.display = 'block';
        if(event) event.currentTarget.classList.add('active');
    }

    function addFurlough() {
        const container = document.getElementById('furloughs-container');
        const div = document.createElement('div');
        div.className = 'furlough-item';
        div.style.cssText = 'display: grid; grid-template-columns: 200px 1fr 40px; gap: 1.5rem; align-items: center; background: rgba(0,0,0,0.1); padding: 1rem; border-radius: 12px; border: 1px solid var(--glass-border);';
        
        div.innerHTML = `
            <select name="furlough_reason[]">
                <option value="0">사유 선택</option>
                <option value="1">군복무</option>
                <option value="2">임신출산</option>
                <option value="3">병환</option>
                <option value="4">유학/연수</option>
                <option value="5">거주지이동</option>
            </select>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.75rem; color: var(--text-muted);">시작</span>
                    <input type="date" name="furlough_start[]">
                </div>
                <span style="color: var(--text-muted);">~</span>
                <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.75rem; color: var(--text-muted);">종료</span>
                    <input type="date" name="furlough_end[]">
                </div>
            </div>
            <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.5rem;">&times;</button>
        `;
        container.appendChild(div);
    }

    function addAward() {
        const container = document.getElementById('awards-container');
        const div = document.createElement('div');
        div.className = 'award-item';
        div.style.cssText = 'display: flex; gap: 0.75rem; align-items: center; background: rgba(255,255,255,0.02); padding: 0.75rem; border-radius: 10px; border: 1px solid var(--glass-border);';
        
        const awardOptions = [3, 5, 10, 15, 20, 25];
        let options = '<option value="">수상 선택</option>';
        awardOptions.forEach(y => {
            options += `<option value="${y}">${y}년 근속상</option>`;
        });

        div.innerHTML = `
            <div style="width: 100px; position: relative;">
                <input type="text" name="award_year[]" placeholder="연도" style="text-align: center; padding-right: 1.5rem;">
                <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-size: 0.65rem; color: var(--text-muted);">년</span>
            </div>
            <div style="flex: 1;">
                <select name="award_name[]">${options}</select>
            </div>
            <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.2rem; padding: 0 0.5rem;">&times;</button>
        `;
        container.appendChild(div);
    }

    function addEducation() {
        const container = document.getElementById('education-container');
        const div = document.createElement('div');
        div.className = 'edu-item glass-card';
        div.style.cssText = 'padding: 1rem; display: grid; grid-template-columns: 1fr 200px 40px; gap: 1.5rem; align-items: center;';
        
        div.innerHTML = `
            <div class="form-group" style="margin: 0;">
                <input type="text" name="edu_title[]" placeholder="교육 과목명">
            </div>
            <div class="form-group" style="margin: 0; position: relative;">
                <input type="text" name="edu_date[]" placeholder="YYYY-MM-DD" style="text-align: center;">
                <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 0.7rem; color: var(--text-muted);">수료일</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.5rem;">&times;</button>
        `;
        container.appendChild(div);
    }
</script>

<style>
    #photo-container:hover .photo-overlay {
        opacity: 1 !important;
    }
</style>

<script>
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${type === 'info' ? 'ℹ️' : '⚠️'}</span> ${message}`;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('photo-preview');
            const placeholder = document.getElementById('photo-placeholder');
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
            showToast('사진이 선택되었습니다. 상단의 [저장하기] 버튼을 눌러야 최종 반영됩니다.', 'info');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<!-- Parish Search Modal -->
<div id="parishModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; backdrop-filter: blur(5px); display: none; align-items: center; justify-content: center;">
    <div class="glass-card" style="width: 500px; max-width: 90%; padding: 2rem; position: relative;">
        <button type="button" onclick="closeParishModal()" style="position: absolute; right: 1.5rem; top: 1.5rem; background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h2 style="margin-bottom: 1.5rem;">본당 검색</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label style="font-size: 0.8rem; color: var(--text-muted);">대리구</label>
                <select id="parish_search_vicariate" onchange="filterDistricts(this.value); searchParish()" style="width: 100%; background: var(--bg-dark);">
                    <option value="">전체 대리구</option>
                    <?php foreach ($vicariates as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['GYOGU']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <label style="font-size: 0.8rem; color: var(--text-muted);">지구</label>
                <select id="parish_search_district" onchange="searchParish()" style="width: 100%; background: var(--bg-dark);">
                    <option value="">전체 지구</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?= $d['id'] ?>" data-vicariate="<?= $d['vicariate_id'] ?>"><?= htmlspecialchars($d['JIGU']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label style="font-size: 0.8rem; color: var(--text-muted);">본당명</label>
            <input type="text" id="parish_search_keyword" placeholder="본당명 입력..." onkeyup="searchParish()" style="width: 100%; padding: 1rem; background: var(--bg-dark); border: 1px solid var(--glass-border); border-radius: 12px; color: var(--text-main);">
        </div>

        <div id="parish_search_results" style="max-height: 250px; overflow-y: auto; margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
            <p style="text-align: center; color: var(--text-muted); padding: 2rem;">검색 조건을 입력하거나 선택하세요</p>
        </div>
    </div>
</div>

<script>
    let parishSearchTimeout = null;

    function openParishModal() {
        const modal = document.getElementById('parishModal');
        modal.style.display = 'flex';
        document.getElementById('parish_search_keyword').focus();
    }

    function closeParishModal() {
        document.getElementById('parishModal').style.display = 'none';
    }

    function filterDistricts(vicariateId) {
        const districtSelect = document.getElementById('parish_search_district');
        const options = districtSelect.options;
        for (let i = 1; i < options.length; i++) {
            const vId = options[i].getAttribute('data-vicariate');
            if (!vicariateId || vId === vicariateId) {
                options[i].hidden = false;
            } else {
                options[i].hidden = true;
            }
        }
        districtSelect.value = '';
    }

    function searchParish() {
        const vicariateId = document.getElementById('parish_search_vicariate').value;
        const districtId = document.getElementById('parish_search_district').value;
        const keyword = document.getElementById('parish_search_keyword').value;

        clearTimeout(parishSearchTimeout);
        parishSearchTimeout = setTimeout(() => {
            if (keyword.length < 1 && !vicariateId && !districtId) {
                document.getElementById('parish_search_results').innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 2rem;">검색 조건을 입력하거나 선택하세요</p>';
                return;
            }

            fetch(`index.php?action=parish_search&vicariate_id=${vicariateId}&district_id=${districtId}&keyword=${encodeURIComponent(keyword)}`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('parish_search_results');
                    if (data.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 2rem;">검색 결과가 없습니다</p>';
                        return;
                    }

                    container.innerHTML = data.map(p => `
                        <div class="search-result-item" onclick="selectParish(${p.id}, '${p.parish_name}', '${p.diocese_name || ''}')" style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 8px; cursor: pointer; transition: 0.2s; border: 1px solid transparent;">
                            <div style="font-weight: 600; color: var(--primary);">[${p.diocese_name || '대리구 없음'}] ${p.parish_name}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">${p.district_name || ''}</div>
                        </div>
                    `).join('');
                });
        }, 300);
    }

    function selectParish(id, name, diocese) {
        document.getElementById('parish_id_input').value = id;
        document.getElementById('parish_name_display').value = `[${diocese}] ${name}`;
        closeParishModal();
    }

    // Add CSS for hover
    const style = document.createElement('style');
    style.textContent = `
        .search-result-item:hover {
            background: rgba(79, 70, 229, 0.1) !important;
            border-color: var(--primary) !important;
        }
    `;
    document.head.appendChild(style);
</script>
