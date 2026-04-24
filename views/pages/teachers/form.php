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

<form id="teacherForm" action="<?= $base ?>index.php?action=save_teacher" method="POST">
    <input type="hidden" name="mode" value="<?= $mode ?>">
    <input type="hidden" name="login_id" value="<?= $teacher['login_id'] ?? '' ?>">

    <div class="dashboard-grid" style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
        
        <!-- Left Column: Photo & Profile Summary -->
        <aside>
            <div class="glass-card" style="text-align: center; padding: 2rem; position: sticky; top: 2rem;">
                <div style="width: 150px; height: 180px; background: var(--bg-dark); border-radius: 12px; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; border: 2px dashed var(--glass-border); overflow: hidden;">
                    <?php if (!empty($teacher['strPhotoFile'])): ?>
                        <img src="<?= $base ?>Photo/<?= $teacher['strPhotoFile'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">사진 없음</span>
                    <?php endif; ?>
                </div>
                <h3 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($teacher['name'] ?? '신규 교사') ?></h3>
                <p style="color: var(--text-muted); font-size: 0.875rem;"><?= htmlspecialchars($teacher['login_id'] ?? '-') ?></p>
                
                <div style="margin-top: 2rem; text-align: left; font-size: 0.875rem; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">부서</span> 
                        <span style="color: var(--primary); font-weight: 600;">
                            <?= ['1'=>'초등', '2'=>'중고', '3'=>'대건', '4'=>'장애', '5'=>'초·중고'][$teacher['academy'] ?? ''] ?? '-' ?>
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
                <button type="button" class="tab-btn" onclick="showTab('assignment')">소속 및 휴직</button>
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
                        <input type="text" name="bname" value="<?= htmlspecialchars($teacher['bname'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>생년월일 (YYMMDD)</label>
                        <input type="text" name="jumin_f" value="<?= htmlspecialchars($teacher['jumin_f'] ?? '') ?>" maxlength="6">
                    </div>
                    <div class="form-group">
                        <label>영명축일 (MM/DD)</label>
                        <input type="text" name="bday" value="<?= htmlspecialchars($teacher['bday'] ?? '') ?>" placeholder="05/15">
                    </div>
                    <div class="form-group">
                        <label>휴대전화</label>
                        <input type="text" name="phone2" value="<?= htmlspecialchars($teacher['phone2'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>자택전화</label>
                        <input type="text" name="phone1" value="<?= htmlspecialchars($teacher['strHomeTel'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>이메일</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($teacher['strEmail'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>주소</label>
                        <div style="display: grid; grid-template-columns: 120px 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <input type="text" name="postcode" value="<?= htmlspecialchars($teacher['strHomePost'] ?? '') ?>" placeholder="우편번호">
                            <button type="button" class="btn" style="background: var(--bg-dark);">주소 찾기</button>
                        </div>
                        <input type="text" name="addr1" value="<?= htmlspecialchars($teacher['strHomeAddr1'] ?? '') ?>" placeholder="기본 주소" style="margin-bottom: 0.5rem;">
                        <input type="text" name="addr2" value="<?= htmlspecialchars($teacher['strHomeAddr2'] ?? '') ?>" placeholder="상세 주소">
                    </div>
                </div>
            </div>

            <!-- 2. 소속 및 휴직 -->
            <div id="tab-assignment" class="tab-content" style="display: none;">
                <h4 style="margin-bottom: 1.5rem; color: var(--primary);">📋 소속 정보</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="form-group">
                        <label>부서</label>
                        <select name="academy">
                            <option value="1" <?= isSelected($teacher['academy'], '1') ?>>초등부</option>
                            <option value="2" <?= isSelected($teacher['academy'], '2') ?>>중고등부</option>
                            <option value="5" <?= isSelected($teacher['academy'], '5') ?>>초·중고등부</option>
                            <option value="3" <?= isSelected($teacher['academy'], '3') ?>>대건</option>
                            <option value="4" <?= isSelected($teacher['academy'], '4') ?>>장애아</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>직책</label>
                        <select name="type_num">
                            <option value="5" <?= isSelected($teacher['type_num'], '5') ?>>평교사</option>
                            <option value="2" <?= isSelected($teacher['type_num'], '2') ?>>교감</option>
                            <option value="3" <?= isSelected($teacher['type_num'], '3') ?>>교무</option>
                            <option value="4" <?= isSelected($teacher['type_num'], '4') ?>>총무</option>
                            <option value="6" <?= isSelected($teacher['type_num'], '6') ?>>분과장</option>
                            <option value="7" <?= isSelected($teacher['type_num'], '7') ?>>휴직교사</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>담당 학년</label>
                        <input type="text" name="ac_edpart02" value="<?= htmlspecialchars($teacher['ac_edpart02'] ?? '') ?>" placeholder="예: 3학년">
                    </div>
                    <div class="form-group">
                        <label>근속 기준 연월</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" name="cs_year" value="<?= htmlspecialchars($teacher['cs_year'] ?? '') ?>" placeholder="YYYY" style="width: 80px;">
                            <input type="text" name="cs_month" value="<?= htmlspecialchars($teacher['cs_month'] ?? '') ?>" placeholder="MM" style="width: 60px;">
                        </div>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>비고 (특이사항)</label>
                        <textarea name="ac_edsc" rows="3"><?= htmlspecialchars($teacher['ac_edsc'] ?? '') ?></textarea>
                    </div>
                </div>

                <h4 style="margin-bottom: 1.5rem; color: var(--danger);">⏸️ 휴직 이력</h4>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php for($i=1; $i<=3; $i++): ?>
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 1rem; align-items: center; background: var(--bg-dark); padding: 1rem; border-radius: 8px;">
                        <select name="reason<?= $i ?>">
                            <option value="0">선택안함</option>
                            <option value="1" <?= isSelected($teacher["reason$i"] ?? '', '1') ?>>군복무</option>
                            <option value="2" <?= isSelected($teacher["reason$i"] ?? '', '2') ?>>임신출산</option>
                            <option value="3" <?= isSelected($teacher["reason$i"] ?? '', '3') ?>>병환</option>
                            <option value="4" <?= isSelected($teacher["reason$i"] ?? '', '4') ?>>유학/연수</option>
                            <option value="5" <?= isSelected($teacher["reason$i"] ?? '', '5') ?>>거주지이동</option>
                        </select>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="date" name="rsdt<?= ($i*2)-1 ?>" value="<?= $teacher["rsdt".(($i*2)-1)] ?? '' ?>">
                            <span>~</span>
                            <input type="date" name="rsdt<?= $i*2 ?>" value="<?= $teacher["rsdt".($i*2)] ?? '' ?>">
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- 3. 상세 교육 -->
            <div id="tab-edu_detail" class="tab-content" style="display: none;">
                <h4 style="margin-bottom: 1.5rem; color: var(--primary);">📚 과목별 수료 현황 (양성교육)</h4>
                <div class="table-container">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                                <th style="padding: 0.75rem; width: 50px;">No</th>
                                <th style="padding: 0.75rem;">과목명</th>
                                <th style="padding: 0.75rem;">수료년도</th>
                                <th style="padding: 0.75rem; text-align: center;">상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $eduDetails = $teacher['edu_details'] ?? [];
                                for($i=1; $i<=10; $i++): 
                                    $curr = array_filter($eduDetails, fn($e) => $e['edu_count'] == $i);
                                    $curr = !empty($curr) ? array_shift($curr) : null;
                            ?>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 0.75rem; color: var(--text-muted);"><?= $i ?></td>
                                <td style="padding: 0.75rem;">
                                    <input type="text" name="edu_title_<?= $i ?>" value="<?= htmlspecialchars($curr['edu_title'] ?? '') ?>" placeholder="과목명을 입력하세요">
                                </td>
                                <td style="padding: 0.75rem;">
                                    <input type="text" name="edu_dt_<?= $i ?>" value="<?= htmlspecialchars($curr['edu_dt'] ?? '') ?>" placeholder="YYYY" style="width: 100px;">
                                </td>
                                <td style="padding: 0.75rem; text-align: center;">
                                    <span class="badge <?= $curr ? 'badge-active' : 'badge-upcoming' ?>">
                                        <?= $curr ? '수료완료' : '미수료' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. 참여 이력 -->
            <div id="tab-history" class="tab-content" style="display: none;">
                <h4 style="margin-bottom: 1.5rem; color: var(--primary);">🕒 교육 및 연수 참가 이력</h4>
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

                <h4 style="margin-top: 3rem; margin-bottom: 1.5rem; color: var(--accent);">🏅 근속상 수상 내역</h4>
                <div class="table-container">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tbody>
                            <?php foreach ($teacher['awards'] ?? [] as $award): ?>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 1rem; width: 100px;"><?= $award['tml_year'] ?>년</td>
                                <td style="padding: 1rem; font-weight: 600;">🏅 <?= $award['tml'] ?>년 근속상</td>
                                <td style="padding: 1rem; color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($award['tml_memo'] ?: '-') ?></td>
                            </tr>
                            <?php endforeach; if(empty($teacher['awards'])): ?>
                            <tr><td colspan="3" style="padding: 2rem; text-align: center; color: var(--text-muted);">수상 내역이 없습니다.</td></tr>
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
        background: none;
        border: none;
        padding: 1rem 0;
        color: var(--text-muted);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        border-bottom: 2px solid transparent;
        white-space: nowrap;
    }
    .tab-btn.active {
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
    }
    .tab-btn:hover {
        color: var(--text-main);
    }
    .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-weight: 700;
    }
    .badge-active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .badge-upcoming { background: rgba(148, 163, 184, 0.2); color: #94a3b8; }
</style>

<script>
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        
        document.getElementById('tab-' + tabName).style.display = 'block';
        event.currentTarget.classList.add('active');
    }
</script>
