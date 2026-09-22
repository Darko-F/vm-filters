<?php
/**
 * @package    VM Smart Filters
 * @copyright  Copyright (C) 2026 topoweryou.com
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

if (!$visible) {
    return;
}
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$instance = 'vmfilters-' . (int) $module->id;
$horizontal = $params->get('orientation', 'vertical') === 'horizontal';
?>
<?php if ($error !== '') : ?>
    <p class="alert alert-secondary" role="status"><?= $escape($error) ?></p>
<?php elseif (!$filters && !$propertyFilters && !$activeCount) : ?>
    <?php if ((int) $params->get('show_empty', 0)) : ?>
        <p class="text-muted small"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_EMPTY')) ?></p>
    <?php endif; ?>
<?php else : ?>
<section class="vm-smartfilters<?= $horizontal ? ' vm-smartfilters-horizontal' : ' vm-smartfilters-vertical' ?>" aria-labelledby="<?= $instance ?>-heading">
    <div class="card-body">
        <div class="vm-smartfilters-toolbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <h2 class="visually-hidden" id="<?= $instance ?>-heading"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_HEADING')) ?></h2>
                <span class="vm-filter-icon" title="<?= $escape(Text::_('MOD_VM_SMARTFILTERS_HEADING')) ?>" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M3 4h18l-7 8v7l-4 2v-9z"/></svg>
                </span>
                <?php if ($activeCount > 0) : ?>
                    <span class="badge rounded-pill text-bg-primary" aria-label="<?= $escape(Text::sprintf('MOD_VM_SMARTFILTERS_ACTIVE', $activeCount)) ?>"><?= $activeCount ?></span>
                <?php endif; ?>
            </div>
            <a class="btn vm-filter-clear-all" href="<?= $escape($clearUrl) ?>" aria-label="<?= $escape(Text::_('MOD_VM_SMARTFILTERS_CLEAR')) ?>" title="<?= $escape(Text::_('MOD_VM_SMARTFILTERS_CLEAR')) ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 4h14l-5 7v7l-4 2v-9zM16 15l5 5m0-5-5 5"/></svg>
            </a>
        </div>
        <?php if ($otherCount > 0) : ?>
            <p class="small text-muted"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_OTHER_ACTIVE')) ?></p>
        <?php endif; ?>
        <?php if ($propertyInvalid) : ?>
            <p class="alert alert-warning" role="alert"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_INVALID_RANGE')) ?></p>
        <?php endif; ?>
        <form action="<?= $escape($action) ?>" method="get" class="vm-smartfilters-form" data-vm-filters data-auto-submit="<?= (int) $params->get('autosubmit', 1) ?>">
            <?php foreach ($hidden as $name => $value) : ?>
                <input type="hidden" name="<?= $escape($name) ?>" value="<?= $escape($value) ?>">
            <?php endforeach; ?>
            <div class="vm-smartfilters-controls d-flex flex-wrap gap-2">
                <?php foreach ($filters as $filter) : ?>
                    <div class="vm-filter-item">
                        <?php if ($horizontal) : ?>
                        <details class="vm-filter-dropdown<?= $filter['selected'] !== '' ? ' vm-filter-active' : '' ?>">
                            <summary class="btn btn-outline-secondary"><span><?= $escape(Text::_($filter['title'])) ?><?php if ($filter['selected'] !== '') : ?><span class="vm-filter-value"><?= $escape(Text::_($filter['selected'])) ?></span><?php endif; ?></span><span class="vm-filter-chevron" aria-hidden="true"></span></summary>
                            <div class="vm-filter-panel">
                        <?php endif; ?>
                        <label class="form-label fw-semibold" for="<?= $instance ?>-<?= $filter['id'] ?>"><?= $escape(Text::_($filter['title'])) ?></label>
                        <select class="form-select<?= $filter['selected'] !== '' ? ' border-primary' : '' ?>" id="<?= $instance ?>-<?= $filter['id'] ?>" name="customfields[<?= $filter['id'] ?>]">
                            <option value=""><?= $escape(Text::_('MOD_VM_SMARTFILTERS_ANY')) ?></option>
                            <?php foreach ($filter['values'] as $value) : ?>
                                <option value="<?= $escape($value) ?>"<?= $value === $filter['selected'] ? ' selected' : '' ?>><?= $escape(Text::_($value)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!(int) $params->get('autosubmit', 1)) : ?>
                            <button type="submit" class="btn btn-primary vm-filter-panel-apply"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_APPLY')) ?></button>
                        <?php else : ?>
                            <noscript><button type="submit" class="btn btn-primary vm-filter-panel-apply"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_APPLY')) ?></button></noscript>
                        <?php endif; ?>
                        <?php if ($horizontal) : ?>
                            </div>
                        </details>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-secondary vm-filter-clear" data-clear-filter hidden aria-label="<?= $escape(Text::sprintf('MOD_VM_SMARTFILTERS_CLEAR_ONE', Text::_($filter['title']))) ?>" title="<?= $escape(Text::sprintf('MOD_VM_SMARTFILTERS_CLEAR_ONE', Text::_($filter['title']))) ?>"><span aria-hidden="true">×</span></button>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($propertyFilters as $property) : ?>
                    <div class="vm-filter-item">
                        <?php if ($horizontal) : ?>
                        <details class="vm-filter-dropdown<?= $property['min'] !== '' || $property['max'] !== '' ? ' vm-filter-active' : '' ?>">
                            <summary class="btn btn-outline-secondary"><span><?= $escape(Text::_('MOD_VM_SMARTFILTERS_' . strtoupper($property['property']))) ?> <span class="vm-filter-unit">(<?= $escape(strtolower($property['unit'])) ?>)</span><?php if ($property['min'] !== '' || $property['max'] !== '') : ?><span class="vm-filter-value"><?= $escape($property['min'] !== '' && $property['max'] !== '' ? $property['min'] . ' – ' . $property['max'] : ($property['min'] !== '' ? '≥ ' . $property['min'] : '≤ ' . $property['max'])) ?></span><?php endif; ?></span><span class="vm-filter-chevron" aria-hidden="true"></span></summary>
                            <div class="vm-filter-panel">
                        <?php endif; ?>
                        <fieldset class="vm-property-range">
                            <legend class="form-label fw-semibold"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_' . strtoupper($property['property']))) ?> <span class="text-muted">(<?= $escape(strtolower($property['unit'])) ?>)</span></legend>
                            <input type="hidden" name="vmfp[<?= $escape($property['key']) ?>][unit]" value="<?= $escape($property['unit']) ?>">
                            <div class="vm-property-bounds">
                                <?php foreach (['min' => 'MOD_VM_SMARTFILTERS_MIN', 'max' => 'MOD_VM_SMARTFILTERS_MAX'] as $bound => $label) : ?>
                                    <div>
                                        <label class="small" for="<?= $instance ?>-<?= $escape($property['key']) ?>-<?= $bound ?>"><?= $escape(Text::_($label)) ?></label>
                                        <input type="text" class="form-control" inputmode="decimal" maxlength="22" pattern="[0-9]+([.,][0-9]{1,8})?" id="<?= $instance ?>-<?= $escape($property['key']) ?>-<?= $bound ?>" name="vmfp[<?= $escape($property['key']) ?>][<?= $bound ?>]" value="<?= $escape($property[$bound]) ?>" placeholder="<?= $escape(Text::_('MOD_VM_SMARTFILTERS_NO_LIMIT')) ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <button type="submit" class="btn btn-primary vm-filter-panel-apply"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_APPLY')) ?></button>
                        <?php if ($horizontal) : ?>
                            </div>
                        </details>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-secondary vm-filter-clear" data-clear-filter hidden aria-label="<?= $escape(Text::sprintf('MOD_VM_SMARTFILTERS_CLEAR_ONE', Text::_('MOD_VM_SMARTFILTERS_' . strtoupper($property['property'])))) ?>" title="<?= $escape(Text::sprintf('MOD_VM_SMARTFILTERS_CLEAR_ONE', Text::_('MOD_VM_SMARTFILTERS_' . strtoupper($property['property'])))) ?>"><span aria-hidden="true">×</span></button>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="vm-smartfilters-status small text-muted mt-2 mb-0" role="status" aria-live="polite" hidden><?= $escape(Text::_('MOD_VM_SMARTFILTERS_LOADING')) ?></p>
        </form>
    </div>
</section>
<?php endif; ?>
