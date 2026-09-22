const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');

test('auto submission, manual mode, status and back-navigation recovery', () => {
    const forms = ['1', '0'].map((autoSubmit) => ({
        dataset: { autoSubmit }, listeners: {}, status: { hidden: true }, attributes: {}, submissions: 0,
        addEventListener(name, handler) { this.listeners[name] = handler; },
        querySelector() { return this.status; },
        querySelectorAll() { return []; },
        setAttribute(key, value) { this.attributes[key] = value; },
        removeAttribute(key) { delete this.attributes[key]; },
        requestSubmit() { this.submissions++; this.listeners.submit(); },
    }));
    const windowEvents = {};
    const document = { readyState: 'complete', querySelectorAll: () => forms };
    vm.runInNewContext(readFileSync('mod_vm_smartfilters/media/js/filters.js', 'utf8'), {
        document, window: { addEventListener: (name, cb) => { windowEvents[name] = cb; } },
    });
    const selectEvent = { target: { matches: (selector) => selector === 'select' } };
    forms.forEach((form) => form.listeners.change(selectEvent));
    assert.equal(forms[0].submissions, 1);
    assert.equal(forms[1].submissions, 0);
    assert.equal(forms[0].status.hidden, false);
    assert.equal(forms[0].attributes['aria-busy'], 'true');
    forms[1].listeners.submit();
    assert.equal(forms[1].status.hidden, false);
    windowEvents.pageshow();
    forms.forEach((form) => {
        assert.equal(form.status.hidden, true);
        assert.equal(form.attributes['aria-busy'], undefined);
    });
});

test('filter panels close on Escape/outside click and reveal invalid inputs', () => {
    const listeners = {};
    const panel = { classList: { remove() {}, add() {} }, getBoundingClientRect: () => ({ left: 20, right: 300 }) };
    const summary = { focused: false, focus() { this.focused = true; } };
    const details = {
        open: true, listeners: {},
        addEventListener(name, handler) { this.listeners[name] = handler; },
        querySelector(selector) { return selector === 'summary' ? summary : panel; },
        contains(target) { return target === summary; },
    };
    const form = {
        dataset: {}, listeners: {},
        querySelectorAll(selector) { return selector === '.vm-filter-dropdown' ? [details] : []; },
        addEventListener(name, handler) { this.listeners[name] = handler; },
    };
    const document = {
        readyState: 'complete', documentElement: { clientWidth: 1000 },
        querySelectorAll() { return [form]; },
        addEventListener(name, handler) { listeners[name] = handler; },
    };
    vm.runInNewContext(readFileSync('mod_vm_smartfilters/media/js/filters.js', 'utf8'), {
        document, window: { addEventListener() {} },
    });
    details.listeners.toggle();
    details.listeners.keydown({ key: 'Escape', stopPropagation() {} });
    assert.equal(details.open, false);
    assert.equal(summary.focused, true);
    form.listeners.invalid({ target: { closest: () => details } });
    assert.equal(details.open, true);
    listeners.click({ target: summary });
    assert.equal(details.open, true);
    listeners.click({ target: {} });
    assert.equal(details.open, false);
});

test('individual clear resets only its field, preserves units and submits in manual mode', () => {
    const control = (value) => ({ value, classList: { remove() {} } });
    const min = control('10');
    const max = control('20');
    const unit = control('MM');
    const other = control('M8');
    const clear = { hidden: true, addEventListener(name, fn) { this[name] = fn; } };
    const item = {
        listeners: {},
        querySelector(selector) { return selector === '[data-clear-filter]' ? clear : null; },
        querySelectorAll() { return [min, max]; },
        addEventListener(name, fn) { this.listeners[name] = fn; },
    };
    const form = {
        dataset: { autoSubmit: '0' }, submissions: 0,
        querySelectorAll(selector) { return selector === '.vm-filter-item' ? [item] : []; },
        addEventListener() {}, requestSubmit() { this.submissions++; },
    };
    vm.runInNewContext(readFileSync('mod_vm_smartfilters/media/js/filters.js', 'utf8'), {
        document: { readyState: 'complete', querySelectorAll: () => [form] },
        window: { addEventListener() {} },
    });
    assert.equal(clear.hidden, false);
    clear.click();
    assert.equal(min.value, '');
    assert.equal(max.value, '');
    assert.equal(unit.value, 'MM');
    assert.equal(other.value, 'M8');
    assert.equal(clear.hidden, true);
    assert.equal(form.submissions, 1);
    min.value = '0';
    item.listeners.input();
    assert.equal(clear.hidden, false, 'Zero is a value and can be cleared');
});
