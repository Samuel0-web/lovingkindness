<?php
$groups ??= [];
?>

<?php foreach ($groups as $groupLabel => $activities): ?>
    <div class="audit__group" data-group="<?= htmlspecialchars($groupLabel) ?>">
        <div class="audit__group-header"><?= htmlspecialchars($groupLabel) ?></div>
        
        <div class="audit__list">
            <?php foreach ($activities as $log): ?>
                <div class="audit__item audit__item--<?= $log['action'] ?>"
                    data-id="<?= $log['id'] ?>"
                    data-action="<?= htmlspecialchars($log['action']) ?>" tabindex="0"
                    data-entity-type="<?= htmlspecialchars($log['entity_type'] ?? '') ?>"
                    data-entity-id="<?= htmlspecialchars(($log['entity_id'] ?? '')) ?>"
                >
                    
                    <!-- Subtle circular icon -->
                    <div class="audit__item-icon">
                        <?php if ($log['action'] === 'create'): ?>
                            <i class="fas fa-user-plus"></i>
                        <?php elseif ($log['action'] === 'update'): ?>
                            <i class="fas fa-user-pen"></i>
                        <?php elseif ($log['action'] === 'delete'): ?>
                            <i class="fas fa-user-minus"></i>
                        <?php elseif ($log['action'] === 'enrollment'): ?>
                            <i class="fas fa-user-graduate"></i>
                        <?php elseif ($log['action'] === 'message'): ?>
                            <i class="fas fa-envelope"></i>
                        <?php elseif ($log['action'] === 'status'): ?>
                            <i class="fas fa-exchange-alt"></i>
                        <?php else: ?>
                            <i class="fas fa-cog"></i>
                        <?php endif; ?>
                    </div>

                    <!-- Description-first content -->
                    <div class="audit__item-main">
                        <div class="audit__item-description">
                            <?= htmlspecialchars($log['description']) ?>
                        </div>
                        <div class="audit__item-title">
                            <span class="audit__actor">
                                <?= htmlspecialchars($log['admin_name']) ?>
                            </span>
                            <span class="audit__action-badge">
                                <?= strtoupper($log['action']) ?>
                            </span>
                        </div>
                        <div class="audit__item-meta">
                            <span class="audit__meta-chip audit__meta-chip--ip">
                                <i class="fas fa-globe"></i> 
                                <?= htmlspecialchars($log['ip_address']) ?>
                            </span>
                            <span class="audit__meta-chip audit__meta-chip--time">
                                <i class="far fa-clock"></i> 
                                <?= htmlspecialchars($log['time']) ?>
                            </span>
                            <?php if (!empty($log['entity_type'])): ?>
                                <span class="audit__meta-chip">
                                    <?= htmlspecialchars(strtoupper($log['entity_type'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right timestamp -->
                    <div class="audit__item-time">
                        <?= htmlspecialchars($log['time']) ?>
                    </div>

                    <!-- Lightweight inline details -->
                    <div class="audit__item-details">
                        <div class="audit__details-box">
                            <div class="audit__details-grid">
                                <div class="audit__details-row">
                                    <span class="audit__details-label">Activity ID</span>
                                    <span class="audit__details-value">
                                        <?= htmlspecialchars($log['id']) ?>
                                    </span>
                                </div>
                                <div class="audit__details-row">
                                    <span class="audit__details-label">Admin ID</span>
                                    <span class="audit__details-value">
                                        <?= htmlspecialchars($log['admin_id']) ?>
                                    </span>
                                </div>
                                <div class="audit__details-row">
                                    <span class="audit__details-label">Action</span>
                                    <span class="audit__details-value">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </div>
                                <div class="audit__details-row">
                                    <span class="audit__details-label">IP Address</span>
                                    <span class="audit__details-value">
                                        <?= htmlspecialchars($log['ip_address']) ?>
                                    </span>
                                </div>
                                <div class="audit__details-row">
                                    <span class="audit__details-label">Entity Type</span>
                                    <span class="audit__details-value">
                                        <?= htmlspecialchars($log['entity_type'] ?? '-') ?>
                                    </span>
                                </div>

                                <div class="audit__details-row">
                                    <span class="audit__details-label">Entity ID</span>
                                    <span class="audit__details-value">
                                        <?= htmlspecialchars((string)($log['entity_id'] ?? '-')) ?>
                                    </span>
                                </div>
                                <div class="audit__details-row">
                                    <span class="audit__details-label">Created At</span>
                                    <span class="audit__details-value">
                                        <?= htmlspecialchars($log['created_at']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>