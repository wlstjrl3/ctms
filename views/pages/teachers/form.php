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


<div class="toast-container" id="toast-container"></div>

<form id="teacherForm" action="<?= $base ?>index.php?action=save_teacher" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="<?= $mode ?>">
    <input type="hidden" name="id" value="<?= $teacher['id'] ?? 0 ?>">

    <div class="dashboard-grid">
        
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
                <p style="color: var(--text-muted); font-size: 0.875rem;"><?= $teacher['id'] ? '#' . $teacher['id'] : '신규' ?></p>
                
                <div style="margin-top: 2rem; text-align: left; font-size: 0.875rem; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">부서</span> 
                        <span style="color: var(--primary); font-weight: 600;">
                            <?= ['1'=>'초등', '2'=>'중고', '5'=>'초·중고', '3'=>'대건', '4'=>'장애'][$teacher['department'] ?? ''] ?? '-' ?>
                        </span>
                    </div>
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">직책</span> 
                        <span style="color: var(--accent); font-weight: 600;">
                            <?= htmlspecialchars(($teacher['position'] ?? '') ?: '교사') ?>
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
                <?php if ($mode === 'edit'): ?>
                <div style="margin-top: 1.5rem; border-top: 1px solid var(--glass-border); padding-top: 1rem;">
                    <button type="button" class="btn" onclick="deleteTeacherProfile()" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); width: 100%; border: 1px solid rgba(239, 68, 68, 0.2); font-size: 0.8rem;">
                        🗑️ 교사 정보 완전 삭제
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Right Column: Tabs/Forms -->
        <div class="glass-card" style="padding: 2rem;">
            <div style="display: flex; gap: 2rem; border-bottom: 1px solid var(--glass-border); margin-bottom: 2rem; overflow-x: auto;">
                <button type="button" class="tab-btn active" onclick="showTab('basic')">인적 사항</button>
                <button type="button" class="tab-btn" onclick="showTab('assignment')">소속 및 수상</button>
                <button type="button" class="tab-btn" onclick="showTab('edu_detail')">상세 교육</button>
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
                        <input type="text" name="jumin_f" value="<?= htmlspecialchars($teacher['birth_date'] ?? '') ?>" placeholder="YYYY-MM-DD" maxlength="10">
                    </div>
                    <div class="form-group">
                        <label>영명축일 (MM/DD)</label>
                        <input type="text" name="bday" value="<?= htmlspecialchars($teacher['bday'] ?? '') ?>" placeholder="05/15">
                    </div>
                    <div class="form-group">
                        <label>휴대전화</label>
                        <input type="text" name="phone1" value="<?= htmlspecialchars($teacher['mobile_phone'] ?? '') ?>" placeholder="010-0000-0000">
                    </div>
                    <div class="form-group">
                        <label>이메일</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($teacher['email'] ?? '') ?>" placeholder="example@mail.com">
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
                            <div class="form-group" style="grid-column: span 2;">
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
                            <div class="form-group">
                                <label>재직 상태</label>
                                <select name="status">
                                    <option value="active" <?= isSelected($teacher['status'] ?? 'active', 'active') ?>>재직</option>
                                    <option value="furlough" <?= isSelected($teacher['status'] ?? '', 'furlough') ?>>휴직</option>
                                    <option value="retired" <?= isSelected($teacher['status'] ?? '', 'retired') ?>>퇴직</option>
                                </select>
                            </div>
                        </div>
                        <div class="glass-card grid-2" style="padding: 2rem; margin-top: 1.5rem;">
                             <div class="form-group">
                                 <label>직책</label>
                                 <select name="position">
                                     <option value="교사" <?= isSelected($teacher['position'] ?? '', '교사') ?>>교사</option>
                                     <option value="교감" <?= isSelected($teacher['position'] ?? '', '교감') ?>>교감</option>
                                     <option value="교무" <?= isSelected($teacher['position'] ?? '', '교무') ?>>교무</option>
                                     <option value="총무" <?= isSelected($teacher['position'] ?? '', '총무') ?>>총무</option>
                                 </select>
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
                                <textarea name="ac_edsc" rows="6" style="width: 100%; background: var(--bg-dark); color: var(--text-main); border: 1px solid var(--glass-border); border-radius: 8px; padding: 0.75rem; font-family: inherit; font-size: 0.875rem; line-height: 1.5;"><?= htmlspecialchars($teacher['current_grade'] ?? '') ?></textarea>
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
                                <div class="furlough-item grid-3-auto" style="align-items: center; background: rgba(0,0,0,0.1); padding: 1rem; border-radius: 12px; border: 1px solid var(--glass-border);">
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
                                            <input type="text" name="furlough_start[]" value="<?= $f['start_date'] ?? '' ?>" placeholder="YYYY-MM-DD" maxlength="10" style="text-align: center;">
                                        </div>
                                        <span style="color: var(--text-muted);">~</span>
                                        <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">종료</span>
                                            <input type="text" name="furlough_end[]" value="<?= $f['end_date'] ?? '' ?>" placeholder="YYYY-MM-DD" maxlength="10" style="text-align: center;">
                                        </div>
                                    </div>
                                    <button type="button" onclick="if(confirm('데이터를 복구할 수 없습니다. 정말 삭제하시겠습니까?')){ this.parentElement.remove(); performAjaxSave(); }" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.5rem;">&times;</button>
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
                            <div id="awards-container" class="grid-2">
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
                                    <button type="button" onclick="if(confirm('데이터를 복구할 수 없습니다. 정말 삭제하시겠습니까?')){ this.parentElement.remove(); performAjaxSave(); }" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.2rem; padding: 0 0.5rem;">&times;</button>
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
                        <span style="background: rgba(79, 70, 229, 0.1); padding: 0.5rem; border-radius: 8px;">📚</span> 교리교사 교육과정 수료현황
                    </h4>
                </div>

                <!-- 핵심 3단계 교육과정 -->
                <div class="glass-card" style="padding: 1.5rem; margin-bottom: 2.5rem; background: rgba(79, 70, 229, 0.03);">
                    <div style="display: grid; grid-template-columns: 1fr 120px 100px 100px; gap: 1rem; align-items: center; padding: 0.5rem 1rem; border-bottom: 1px solid var(--glass-border); margin-bottom: 1rem; color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">
                        <div>단계</div>
                        <div style="text-align: center;">년도</div>
                        <div style="text-align: center;">월</div>
                        <div style="text-align: center;">수료</div>
                    </div>
                    <?php 
                        $coreStages = [
                            '기본교육(구입문과정)' => '기본교육(구입문과정)',
                            '구심화과정' => '구심화과정',
                            '양성교육(구전문화과정)' => '양성교육(구전문화과정)'
                        ];
                        $coreEdu = $teacher['core_edu'] ?? [];
                        foreach ($coreStages as $stageName => $displayName):
                            $edu = $coreEdu[$stageName] ?? ['year' => '', 'month' => '', 'is_completed' => false];
                    ?>
                    <div style="display: grid; grid-template-columns: 1fr 120px 100px 100px; gap: 1rem; align-items: center; padding: 0.75rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <div style="font-weight: 600; color: var(--text-main);"><?= $displayName ?></div>
                        <div style="position: relative;">
                            <input type="text" name="core_year[<?= $stageName ?>]" value="<?= htmlspecialchars($edu['year']) ?>" placeholder="YYYY" style="text-align: center; padding-right: 1.5rem;">
                            <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-size: 0.65rem; color: var(--text-muted);">년</span>
                        </div>
                        <div style="position: relative;">
                            <input type="text" name="core_month[<?= $stageName ?>]" value="<?= htmlspecialchars((string)$edu['month']) ?>" placeholder="MM" style="text-align: center; padding-right: 1.5rem;">
                            <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-size: 0.65rem; color: var(--text-muted);">월</span>
                        </div>
                        <div style="text-align: center;">
                            <label class="switch">
                                <input type="checkbox" name="core_in[<?= $stageName ?>]" value="1" <?= $edu['is_completed'] ? 'checked' : '' ?> data-original="<?= $edu['is_completed'] ? 'true' : 'false' ?>">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h4 style="color: var(--primary); display: flex; align-items: center; gap: 0.75rem; font-size: 1.1rem;">
                        <span style="background: rgba(79, 70, 229, 0.1); padding: 0.5rem; border-radius: 8px;">📖</span> 과목별 수료 현황
                    </h4>
                    <button type="button" class="btn" onclick="addEducation()" style="padding: 0.5rem 1rem; font-size: 0.8rem; background: rgba(79, 70, 229, 0.1); color: var(--primary); border: 1px solid rgba(79, 70, 229, 0.2);">
                        + 교육 추가
                    </button>
                </div>
                
                <div id="education-container" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php 
                        $eduDetails = $teacher['edu_details'] ?? [];
                        foreach ($eduDetails as $idx => $edu): 
                    ?>
                    <div class="edu-item glass-card" style="padding: 1rem; align-items: center; display: grid; grid-template-columns: 1fr 180px 40px; gap: 1.5rem;">
                        <div class="form-group" style="margin: 0;">
                            <div style="display: grid; grid-template-columns: 1fr 80px; gap: 0.5rem;">
                                <input type="text" id="edu_name_<?= $idx ?>" value="<?= htmlspecialchars($edu['edu_title'] ?? '') ?>" readonly placeholder="교육 과목을 선택하세요" style="background: var(--bg-dark); cursor: default;">
                                <input type="hidden" name="edu_course_id[]" id="edu_id_<?= $idx ?>" value="<?= $edu['course_id'] ?? '' ?>">
                                <button type="button" class="btn" onclick="openEducationModal(<?= $idx ?>)" style="background: var(--primary); color: white; padding: 0.5rem;">선택</button>
                            </div>
                        </div>
                        <div class="form-group" style="margin: 0; position: relative;">
                            <span style="position: absolute; right: 0; top: -18px; font-size: 0.65rem; color: var(--text-muted);">수료일</span>
                            <input type="text" name="edu_date[]" value="<?= htmlspecialchars($edu['edu_dt'] ?? '') ?>" placeholder="YYYY-MM-DD" maxlength="10" style="text-align: center;">
                        </div>
                        <button type="button" onclick="if(confirm('데이터를 복구할 수 없습니다. 정말 삭제하시겠습니까?')){ this.parentElement.remove(); performAjaxSave(); }" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.5rem;">&times;</button>
                    </div>
                    <?php endforeach; ?>
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

    /* Switch Styles */
    .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.1); transition: .4s; border: 1px solid var(--glass-border); }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 2px; background-color: white; transition: .4s; }
    input:checked + .slider { background-color: var(--primary); border-color: var(--primary); }
    input:focus + .slider { box-shadow: 0 0 1px var(--primary); }
    input:checked + .slider:before { transform: translateX(20px); }
    .slider.round { border-radius: 34px; }
    .slider.round:before { border-radius: 50%; }
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
                    <input type="text" name="furlough_start[]" placeholder="YYYY-MM-DD" maxlength="10" style="text-align: center;">
                </div>
                <span style="color: var(--text-muted);">~</span>
                <div style="flex: 1; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.75rem; color: var(--text-muted);">종료</span>
                    <input type="text" name="furlough_end[]" placeholder="YYYY-MM-DD" maxlength="10" style="text-align: center;">
                </div>
            </div>
            <button type="button" onclick="if(confirm('데이터를 복구할 수 없습니다. 정말 삭제하시겠습니까?')){ this.parentElement.remove(); performAjaxSave(); }" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.5rem;">&times;</button>
        `;
        container.appendChild(div);
        initAutoSave();
        performAjaxSave();
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
            <button type="button" onclick="if(confirm('데이터를 복구할 수 없습니다. 정말 삭제하시겠습니까?')){ this.parentElement.remove(); performAjaxSave(); }" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.2rem; padding: 0 0.5rem;">&times;</button>
        `;
        container.appendChild(div);
        initAutoSave();
        performAjaxSave();
    }

    let eduIdx = <?= count($eduDetails) ?>;
    function addEducation() {
        const container = document.getElementById('education-container');
        const div = document.createElement('div');
        div.className = 'edu-item glass-card';
        div.style.cssText = 'padding: 1rem; align-items: center; gap: 1rem; display: grid; grid-template-columns: 1fr 180px 40px; gap: 1.5rem;';
        
        div.innerHTML = `
            <div class="form-group" style="margin: 0;">
                <div style="display: grid; grid-template-columns: 1fr 80px; gap: 0.5rem;">
                    <input type="text" id="edu_name_${eduIdx}" readonly placeholder="교육 과목을 선택하세요" style="background: var(--bg-dark); cursor: default;">
                    <input type="hidden" name="edu_course_id[]" id="edu_id_${eduIdx}">
                    <button type="button" class="btn" onclick="openEducationModal(${eduIdx})" style="background: var(--primary); color: white; padding: 0.5rem;">선택</button>
                </div>
            </div>
            <div class="form-group" style="margin: 0; position: relative;">
                <span style="position: absolute; right: 0; top: -18px; font-size: 0.65rem; color: var(--text-muted);">수료일</span>
                <input type="text" name="edu_date[]" placeholder="YYYY-MM-DD" maxlength="10" style="text-align: center;">
            </div>
            <button type="button" onclick="if(confirm('데이터를 복구할 수 없습니다. 정말 삭제하시겠습니까?')){ this.parentElement.remove(); performAjaxSave(); }" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1.5rem;">&times;</button>
        `;
        container.appendChild(div);
        initAutoSave();
        performAjaxSave();
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
            showAutoSaveStatus('저장 중...');
            performAjaxSave().then(success => {
                if (success) showAutoSaveStatus('사진 저장됨');
                else alert('사진 저장 중 오류가 발생했습니다.');
            });
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// --- Auto-Save Logic ---
let isSaving = false;

function initAutoSave() {
    // Standard inputs
    document.querySelectorAll('input, select, textarea').forEach(el => {
        if (!el.name || ['id', 'mode', 'photo'].includes(el.name)) return;
        
        // Handle dynamic arrays (furlough, awards, edu)
        if (el.name.includes('[]')) {
            el.addEventListener('change', () => {
                showAutoSaveStatus('변경 중...');
                performAjaxSave().then(success => {
                    if (success) showAutoSaveStatus('목록 업데이트됨');
                });
            });
            return;
        }

        el.setAttribute('data-original', el.value);

        // Listen for changes
        const eventType = el.tagName === 'SELECT' || el.type === 'date' || el.type === 'checkbox' ? 'change' : 'blur';
        el.addEventListener(eventType, handleFieldChange);
        
        if (el.tagName === 'INPUT' && el.type === 'text') {
            el.addEventListener('keypress', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    el.blur();
                }
            });
        }
    });
}

async function handleFieldChange(e) {
    const el = e.target;
    const original = el.type === 'checkbox' ? (el.getAttribute('data-original') === 'true') : el.getAttribute('data-original');
    const current = el.type === 'checkbox' ? el.checked : el.value;

    if (original === current || isSaving) return;

    const labelEl = el.closest('.form-group')?.querySelector('label');
    const label = labelEl ? labelEl.innerText : el.name;

    // Map numeric values to labels for clearer confirmation
    const academyMap = {
        '1': '초등부',
        '2': '중고등부',
        '5': '대건',
        '3': '장애아',
        '4': '초·중고 통합'
    };

    const statusMap = {
        'active': '재직',
        'furlough': '휴직',
        'retired': '퇴직'
    };

    let displayOriginal = original;
    let displayCurrent = current;

    if (el.name === 'academy') {
        displayOriginal = academyMap[original] || original;
        displayCurrent = academyMap[current] || current;
    } else if (el.name === 'status') {
        displayOriginal = statusMap[original] || original;
        displayCurrent = statusMap[current] || current;
    }

    let shouldSave = false;
    if (!original || original.trim() === '') {
        // Case 1: Empty to Non-empty -> Save immediately
        shouldSave = true;
    } else {
        // Case 2: Existing value changed -> Confirm with labels
        shouldSave = confirm(`[${label}] 정보를 수정하시겠습니까?\n\n기존: ${displayOriginal}\n변경: ${displayCurrent}`);
    }

    if (shouldSave) {
        const success = await performAjaxSave();
        if (success) {
            el.setAttribute('data-original', el.type === 'checkbox' ? el.checked : current);
            showAutoSaveStatus('저장됨');
        } else {
            alert('저장 중 오류가 발생했습니다.');
            el.value = original;
        }
    } else {
        el.value = original;
    }
}

async function performAjaxSave() {
    isSaving = true;
    const form = document.getElementById('teacherForm');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('index.php?action=ajax_save_teacher', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        // If we were in create mode and just created a teacher, update the ID and switch to edit mode
        if (result.success && result.id) {
            const idInput = document.querySelector('input[name="id"]');
            const modeInput = document.querySelector('input[name="mode"]');
            if (idInput) idInput.value = result.id;
            if (modeInput) modeInput.value = 'edit';
        }

        isSaving = false;
        return result.success;
    } catch (error) {
        console.error('Auto-save error:', error);
        isSaving = false;
        return false;
    }
}

function showAutoSaveStatus(text) {
    let statusEl = document.getElementById('autosave-status');
    if (!statusEl) {
        statusEl = document.createElement('div');
        statusEl.id = 'autosave-status';
        statusEl.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: var(--primary); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.8rem; z-index: 2000; box-shadow: 0 4px 12px rgba(0,0,0,0.3); transition: opacity 0.3s; pointer-events: none;';
        document.body.appendChild(statusEl);
    }
    statusEl.innerText = text;
    statusEl.style.opacity = '1';
    setTimeout(() => {
        statusEl.style.opacity = '0';
    }, 2000);
}

document.addEventListener('DOMContentLoaded', () => {
    initAutoSave();
    
    // Feast Day auto-formatter (MM/DD)
    const bdayInput = document.querySelector('input[name="bday"]');
    if (bdayInput) {
        bdayInput.addEventListener('input', function(e) {
            let val = this.value.replace(/\D/g, '');
            if (val.length > 4) val = val.substring(0, 4);
            if (val.length >= 3) {
                this.value = val.substring(0, 2) + '/' + val.substring(2);
            } else {
                this.value = val;
            }
        });
    }

    // Phone auto-formatter (010-0000-0000)
    const phoneInput = document.querySelector('input[name="phone2"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let val = this.value.replace(/\D/g, '');
            if (val.length > 11) val = val.substring(0, 11);
            
            let formatted = '';
            if (val.length <= 3) {
                formatted = val;
            } else if (val.length <= 7) {
                formatted = val.substring(0, 3) + '-' + val.substring(3);
            } else if (val.length <= 10) {
                formatted = val.substring(0, 3) + '-' + val.substring(3, 6) + '-' + val.substring(6);
            } else {
                formatted = val.substring(0, 3) + '-' + val.substring(3, 7) + '-' + val.substring(7);
            }
            this.value = formatted;
        });
    }

    // Birth Date auto-formatter (YYYY-MM-DD)
    const birthInput = document.querySelector('input[name="jumin_f"]');
    if (birthInput) {
        birthInput.addEventListener('input', function(e) {
            let val = this.value.replace(/\D/g, '');
            if (val.length > 8) val = val.substring(0, 8);
            
            let formatted = '';
            if (val.length <= 4) {
                formatted = val;
            } else if (val.length <= 6) {
                formatted = val.substring(0, 4) + '-' + val.substring(4);
            } else {
                formatted = val.substring(0, 4) + '-' + val.substring(4, 6) + '-' + val.substring(6);
            }
            this.value = formatted;
        });
    }

    // Date auto-formatter (YYYY-MM-DD) for dynamic fields
    document.addEventListener('input', function(e) {
        const dateFields = ['furlough_start[]', 'furlough_end[]', 'edu_date[]'];
        if (dateFields.includes(e.target.name)) {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length > 8) val = val.substring(0, 8);
            
            let formatted = '';
            if (val.length <= 4) {
                formatted = val;
            } else if (val.length <= 6) {
                formatted = val.substring(0, 4) + '-' + val.substring(4);
            } else {
                formatted = val.substring(0, 4) + '-' + val.substring(4, 6) + '-' + val.substring(6);
            }
            e.target.value = formatted;
        }
    });
});

function deleteTeacherProfile() {
    if (confirm('이 교사의 모든 정보(이력, 수상, 교육 등)가 영구적으로 삭제됩니다.\n데이터를 복구할 수 없습니다. 정말 삭제하시겠습니까?')) {
        const teacherId = document.querySelector('input[name="id"]').value;
        const base = '<?= $base ?>';
        window.location.href = `${base}index.php?action=teacher_delete&id=${teacherId}`;
    }
}
// --- End Auto-Save Logic ---
</script>

<!-- Parish Search Modal -->
<div id="parishModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
    <div class="glass-card" style="width: 550px; max-width: 95%; padding: 1.25rem; position: relative;">
        <button type="button" onclick="closeParishModal()" style="position: absolute; right: 1rem; top: 1rem; background: none; border: none; color: var(--text-muted); font-size: 1.25rem; cursor: pointer;">&times;</button>
        <h3 style="margin-bottom: 1.25rem; font-size: 1.1rem;">본당 검색</h3>
        
        <div style="display: grid; grid-template-columns: 120px 110px 1fr; gap: 0.5rem; margin-bottom: 1rem;">
            <div class="form-group" style="margin: 0;">
                <select id="parish_search_vicariate" onchange="filterDistricts(this.value); searchParish()" style="width: 100%; background: var(--bg-dark); color: var(--text-main); border: 1px solid var(--glass-border); border-radius: 6px; padding: 0.4rem; font-size: 0.85rem;">
                    <option value="">대리구 전체</option>
                    <?php foreach ($vicariates as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['GYOGU']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <select id="parish_search_district" onchange="searchParish()" style="width: 100%; background: var(--bg-dark); color: var(--text-main); border: 1px solid var(--glass-border); border-radius: 6px; padding: 0.4rem; font-size: 0.85rem;">
                    <option value="">지구 전체</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?= $d['id'] ?>" data-vicariate="<?= $d['vicariate_id'] ?>"><?= htmlspecialchars($d['JIGU']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <input type="text" id="parish_search_keyword" placeholder="본당명 검색..." onkeyup="searchParish()" style="width: 100%; padding: 0.4rem 0.75rem; background: var(--bg-dark); border: 1px solid var(--glass-border); border-radius: 6px; color: var(--text-main); font-size: 0.85rem;">
            </div>
        </div>

        <div id="parish_search_results" style="max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px;">
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
                        <div class="search-result-item" onclick="selectParish(${p.id}, '${p.parish_name}', '${p.diocese_name || ''}')" style="cursor: pointer; transition: 0.2s;">
                            <div style="font-weight: 600; font-size: 0.875rem; display: flex; align-items: center; gap: 4px;">
                                <span style="color: var(--text-muted); font-size: 0.75rem;">[${p.diocese_name || '대리구'}]</span>
                                <span style="color: var(--accent); font-size: 0.75rem;">[${p.district_name || '지구'}]</span>
                                <span style="color: var(--primary); margin-left: 2px;">${p.parish_name}</span>
                            </div>
                        </div>
                    `).join('');
                });
        }, 300);
    }

    function selectParish(id, name, diocese) {
        document.getElementById('parish_id_input').value = id;
        document.getElementById('parish_name_display').value = `[${diocese}] ${name}`;
        closeParishModal();
        performAjaxSave(); // Trigger auto-save immediately after binding
    }

    // --- Education Course Search ---
    let currentEduTargetIdx = null;
    let eduSearchTimeout = null;

    function openEducationModal(idx) {
        currentEduTargetIdx = idx;
        const modal = document.getElementById('educationModal');
        modal.style.display = 'flex';
        document.getElementById('edu_search_keyword').value = '';
        document.getElementById('edu_search_keyword').focus();
        searchEducation();
    }

    function closeEducationModal() {
        document.getElementById('educationModal').style.display = 'none';
    }

    function searchEducation() {
        const keyword = document.getElementById('edu_search_keyword').value;
        const category = document.getElementById('edu_search_category').value;
        clearTimeout(eduSearchTimeout);
        eduSearchTimeout = setTimeout(() => {
            fetch(`index.php?action=ajax_course_search&keyword=${encodeURIComponent(keyword)}&category=${encodeURIComponent(category)}`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('edu_search_results');
                    if (data.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 2rem;">검색 결과가 없습니다</p>';
                        return;
                    }

                    container.innerHTML = data.map(c => `
                        <div class="search-result-item" onclick="selectEducation(${c.id}, '${c.course_name.replace("'", "\\'")}')" style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 8px; cursor: pointer; transition: 0.2s; border: 1px solid transparent;">
                            <div style="font-weight: 600; color: var(--primary);">${c.course_name}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">${c.category}</div>
                        </div>
                    `).join('');
                });
        }, 300);
    }

    function selectEducation(id, name) {
        if (currentEduTargetIdx !== null) {
            document.getElementById(`edu_id_${currentEduTargetIdx}`).value = id;
            document.getElementById(`edu_name_${currentEduTargetIdx}`).value = name;
            performAjaxSave();
        }
        closeEducationModal();
    }

    // Add CSS for hover
    const style = document.createElement('style');
    style.textContent = `
        .search-result-item:hover {
            background: rgba(79, 70, 229, 0.1) !important;
            border-color: var(--primary) !important;
        }
        .search-result-item {
            padding: 0.6rem 0.85rem !important;
            background: rgba(255,255,255,0.03) !important;
            border-radius: 6px !important;
        }
    `;
    document.head.appendChild(style);
</script>

<!-- Education Search Modal -->
<div id="educationModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
    <div class="glass-card" style="width: 500px; max-width: 90%; padding: 2rem; position: relative;">
        <button type="button" onclick="closeEducationModal()" style="position: absolute; right: 1.5rem; top: 1.5rem; background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h2 style="margin-bottom: 1.5rem;">교육 과정 검색</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 120px; gap: 0.5rem; margin-bottom: 1rem;">
            <div class="form-group" style="margin: 0;">
                <input type="text" id="edu_search_keyword" placeholder="교육 명칭 검색..." onkeyup="searchEducation()" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-main);">
            </div>
            <div class="form-group" style="margin: 0;">
                <select id="edu_search_category" onchange="searchEducation()" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-main);">
                    <option value="all">전체</option>
                    <option value="[미분류]">[미분류]</option>
                    <option value="영성 교육">영성 교육</option>
                    <option value="교리/신학">교리/신학</option>
                    <option value="교수법/심리">교수법/심리</option>
                    <option value="기능/기술">기능/기술</option>
                    <option value="리더십/소통">리더십/소통</option>
                    <option value="기타">기타</option>
                </select>
            </div>
        </div>

        <div id="edu_search_results" style="max-height: 350px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
            <p style="text-align: center; color: var(--text-muted); padding: 2rem;">검색어를 입력하거나 카테고리를 선택하세요</p>
        </div>
        
        <div style="margin-top: 1.5rem; text-align: center;">
            <p style="font-size: 0.8rem; color: var(--text-muted);">원하는 교육이 없나요? <a href="<?= $base ?>index.php?page=education_list" style="color: var(--primary); text-decoration: underline;">교육 과정 관리</a>에서 추가하세요.</p>
        </div>
    </div>
</div>
