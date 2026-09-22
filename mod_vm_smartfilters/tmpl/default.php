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
?>
<?php if ($error !== '') : ?>
    <p class="alert alert-secondary" role="status"><?= $escape($error) ?></p>
<?php elseif (!$filters && !$activeCount) : ?>
    <?php if ((int) $params->get('show_empty', 0)) : ?>
        <p class="text-muted small"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_EMPTY')) ?></p>
    <?php endif; ?>
<?php else : ?>
<section class="vm-smartfilters card border-0 shadow-sm rounded-4" aria-labelledby="<?= $instance ?>-heading">
    <div class="card-body p-4">
        <div class="vm-smartfilters-heading d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 mb-0" id="<?= $instance ?>-heading"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_HEADING')) ?></h2>
            <?php if ($activeCount > 0) : ?>
                <span class="badge rounded-pill text-bg-primary" aria-label="<?= $escape(Text::sprintf('MOD_VM_SMARTFILTERS_ACTIVE', $activeCount)) ?>"><?= $activeCount ?></span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-4" id="<?= $instance ?>-help"><?= $escape(Text::_((int) $params->get('autosubmit', 1) ? 'MOD_VM_SMARTFILTERS_HINT_AUTO' : 'MOD_VM_SMARTFILTERS_HINT')) ?></p>
        <?php if ($otherCount > 0) : ?>
            <p class="small text-muted"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_OTHER_ACTIVE')) ?></p>
        <?php endif; ?>
        <form action="<?= $escape($action) ?>" method="get" class="vm-smartfilters-form" data-vm-filters data-auto-submit="<?= (int) $params->get('autosubmit', 1) ?>" aria-describedby="<?= $instance ?>-help">
            <?php foreach ($hidden as $name => $value) : ?>
                <input type="hidden" name="<?= $escape($name) ?>" value="<?= $escape($value) ?>">
            <?php endforeach; ?>
            <div class="row g-3">
                <?php foreach ($filters as $filter) : ?>
                    <div class="<?= $params->get('orientation', 'vertical') === 'horizontal' ? 'col-12 col-md-6 col-xl-4' : 'col-12' ?>">
                        <label class="form-label fw-semibold" for="<?= $instance ?>-<?= $filter['id'] ?>"><?= $escape(Text::_($filter['title'])) ?></label>
                        <select class="form-select<?= $filter['selected'] !== '' ? ' border-primary' : '' ?>" id="<?= $instance ?>-<?= $filter['id'] ?>" name="customfields[<?= $filter['id'] ?>]">
                            <option value=""><?= $escape(Text::_('MOD_VM_SMARTFILTERS_ANY')) ?></option>
                            <?php foreach ($filter['values'] as $value) : ?>
                                <option value="<?= $escape($value) ?>"<?= $value === $filter['selected'] ? ' selected' : '' ?>><?= $escape(Text::_($value)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="vm-smartfilters-actions d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary flex-grow-1"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_APPLY')) ?></button>
                <a class="btn btn-outline-secondary" href="<?= $escape($clearUrl) ?>"><?= $escape(Text::_('MOD_VM_SMARTFILTERS_CLEAR')) ?></a>
            </div>
            <p class="vm-smartfilters-status small text-muted mt-2 mb-0" role="status" aria-live="polite" hidden><?= $escape(Text::_('MOD_VM_SMARTFILTERS_LOADING')) ?></p>
        </form>
    </div>
</section>
<?php endif; ?>
