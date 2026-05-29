(function($) {
    const Templates = {
    mainContainer: (id, btnColor, btnText) => `
        <div class="main-container" data-id="${id}" data-type="main">
            <div class="container-controls">
                <button class="edit-container" data-id="${id}" title="Settings">⚙️</button>
                <button class="remove-container" data-id="${id}" title="Remove">🗑️</button>
            </div>
            <div class="drop-zone main-drop-zone" data-parent="${id}" data-zone-type="main">
                <div class="container-placeholder">📦 Drag items here (fields, sub divs, social buttons)</div>
            </div>
            <div class="container-submit">
                <button class="submit-btn" style="background: ${btnColor}">${btnText}</button>
            </div>
        </div>
    `,

    subDiv: (id, bgColor = '#f8f9fa') => `
        <div class="sub-div" data-id="${id}" data-type="sub" style="background: ${bgColor};">
            <div class="container-controls">
                <button class="edit-subdiv" data-id="${id}" title="Settings">⚙️</button>
                <button class="remove-subdiv" data-id="${id}" title="Remove">🗑️</button>
            </div>
            <div class="drop-zone sub-drop-zone" data-parent="${id}" data-zone-type="sub">
                <div class="container-placeholder">📦 Drag fields, sub divs, or social buttons here</div>
            </div>
        </div>
    `,

    socialSection: (id) => `
        <div class="social-section" data-id="${id}" data-type="social_section">
            <div class="section-title">🔗 Social Login</div>
            <div class="container-controls">
                <button class="edit-social" data-id="${id}" title="Settings">⚙️</button>
                <button class="remove-social" data-id="${id}" title="Remove">🗑️</button>
            </div>
            <div class="drop-zone social-drop-zone" data-parent="${id}" data-zone-type="social">
                <div class="container-placeholder">📦 Drag social login buttons here</div>
            </div>
        </div>
    `,

    field: (id, fieldType, label, htmlType, placeholder, showLabels, required = false) => {
        const isCheckbox = htmlType === 'checkbox';
        const isRadio = htmlType === 'radio';
        const fieldClass = isCheckbox ? 'checkbox-field' : (isRadio ? 'radio-field' : '');
        
        let inputHtml = Templates.getInputHtml(htmlType, id, placeholder, required, label);
        let labelHtml = '';
        
        if (showLabels && !isCheckbox && !isRadio) {
            labelHtml = `<label class="field-label" data-field-id="${id}">${label}<span class="edit-hint">✏️</span></label>`;
        }
        
        return `
            <div class="form-field ${fieldClass}" data-id="${id}" data-type="field" data-field-type="${fieldType}" data-html-type="${htmlType}">
                ${labelHtml}
                ${inputHtml}
                <button class="field-drag" title="Drag to reorder">⋮⋮</button>
                <button class="field-edit" data-id="${id}" title="Settings">⚙️</button>
                <button class="field-remove" data-id="${id}" title="Remove">🗑️</button>
            </div>
        `;
    },

    getInputHtml: (type, id, placeholder, required, label) => {
        const requiredAttr = required ? 'required' : '';
        const inputs = {
            text: `<input type="text" class="field-input" placeholder="${placeholder}" ${requiredAttr}>`,
            email: `<input type="email" class="field-input" placeholder="${placeholder}" ${requiredAttr}>`,
            password: `<input type="password" class="field-input" placeholder="${placeholder}" ${requiredAttr}>`,
            number: `<input type="number" class="field-input" placeholder="${placeholder}" ${requiredAttr}>`,
            tel: `<input type="tel" class="field-input" placeholder="${placeholder}" ${requiredAttr}>`,
            date: `<input type="date" class="field-input" ${requiredAttr}>`,
            textarea: `<textarea class="field-input" placeholder="${placeholder}" ${requiredAttr} rows="4"></textarea>`,
            select: `<select class="field-input" ${requiredAttr}><option value="">Select option</option><option value="1">Option 1</option><option value="2">Option 2</option></select>`,
            checkbox: `<input type="checkbox" class="field-input" id="chk_${id}" ${requiredAttr}> <label for="chk_${id}">${label}</label>`,
            radio: `<input type="radio" class="field-input" name="radio_${id}" id="rad_${id}" ${requiredAttr}> <label for="rad_${id}">${label}</label>`
        };
        return inputs[type] || inputs.text;
    },

    socialButton: (provider, color, label) => `
        <div class="social-btn" data-provider="${provider}" style="background: ${color}">${label}</div>
    `,

    placeholder: () => `<div class="container-placeholder">📦 Drag items here</div>`,
    
    emptyBuilder: () => `<div class="builder-placeholder">📦 Drag a Main Container here to start</div>`,
    
    emptyState: () => `<div class="empty-state">No forms created yet</div>`
};

// ============================================
// FORM BUILDER CLASS
// ============================================

class FormBuilder {
    
    containers = {};
    elementCounter = 0;
    currentFormId = null;
    currentFormKey = null;
    selectedElementId = null;
    draggedItemData = null;
    
    settings = {
        btn_color: '#0073aa',
        button_text: 'Submit',
        show_labels: false
    };
    
    constructor() {
        this.init();
    }
    
    init(formId = null) {
        this.currentFormId = formId || MyLoginDesigner.current_form_id;
        this.currentFormKey = MyLoginDesigner.current_form_key || this.currentFormId;
        
        if (this.currentFormId) {
            this.loadForm(this.currentFormId);
        } else if (MyLoginDesigner.forms && MyLoginDesigner.forms.length > 0) {
            const firstForm = MyLoginDesigner.forms[0];
            this.currentFormId = firstForm.id;
            this.currentFormKey = firstForm.form_key;
            this.loadForm(this.currentFormId);
        } else {
            this.showEmptyState();
        }
        
        this.initDraggable();
        this.initDroppable();
        this.initEvents();
        this.initTabs();
        this.initModals();
    }
    
    showEmptyState() {
        $('#formBuilder').html(Templates.emptyBuilder());
        this.initMainContainerDrop();
    }
    
    initDraggable() {
        $('.draggable-field').draggable({
            helper: 'clone',
            revert: 'invalid',
            cursor: 'move',
            opacity: 0.7,
            zIndex: 1000,
            start: (e, ui) => {
                this.draggedItemData = {
                    type: $(e.target).data('drag-type'),
                    fieldType: $(e.target).data('field-type'),
                    fieldLabel: $(e.target).data('field-label'),
                    fieldHtmlType: $(e.target).data('field-html-type'),
                    socialProvider: $(e.target).data('social-provider')
                };
            },
            stop: () => {
                this.draggedItemData = null;
            }
        });
    }
    
    initDroppable() {
        this.initMainContainerDrop();
    }
    
    initMainContainerDrop() {
    // Destroy existing droppable if it exists
    if ($('#formBuilder').hasClass('ui-droppable')) {
        $('#formBuilder').droppable('destroy');
    }
    
    $('#formBuilder').droppable({
        accept: '.main-draggable',
        tolerance: 'pointer',
        hoverClass: 'drag-over',
        drop: (e, ui) => {
            this.addMainContainer();
            $('.builder-placeholder').remove();
            return false;
        }
    });
}
    
makeDroppable(containerId, type) {
    // Select the specific drop zone
    let $dropZone;
    
    if (type === 'main') {
        $dropZone = $(`.main-container[data-id="${containerId}"] .main-drop-zone`);
    } else if (type === 'sub') {
        $dropZone = $(`.sub-div[data-id="${containerId}"] .sub-drop-zone`);
    } else if (type === 'social') {
        $dropZone = $(`.social-section[data-id="${containerId}"] .social-drop-zone`);
    } else {
        return;
    }
    
    if ($dropZone.length === 0) return;
    
    // Destroy existing droppable
    if ($dropZone.hasClass('ui-droppable')) {
        $dropZone.droppable('destroy');
    }
    
    $dropZone.droppable({
        accept: (draggable) => {
            const dragType = draggable.data('drag-type');
            if (type === 'social') return dragType === 'social';
            // Only accept if the draggable is not already inside this drop zone
            const isAlreadyInside = $dropZone.find(draggable).length > 0;
            return !isAlreadyInside && (dragType === 'field' || dragType === 'sub_div' || dragType === 'social_section' || dragType === 'social');
        },
        tolerance: 'pointer',
        hoverClass: 'drag-over',
        greedy: true,
        drop: (e, ui) => {
            e.stopPropagation();
            
            // Get the actual draggable element
            const $draggable = ui.draggable;
            const dragType = $draggable.data('drag-type');
            
            // Prevent dropping into the same container
            if ($dropZone.find($draggable).length > 0) {
                return false;
            }
            
            this.handleDrop(containerId, $draggable);
            return false;
        }
    });
}
    
    handleDrop(parentId, $draggable) {
    const dragType = $draggable.data('drag-type');
    const isExisting = $draggable.closest('.form-field, .sub-div, .social-section').length > 0;
    
    // If dragging an existing element, remove it from its original location
    if (isExisting) {
        const originalParent = $draggable.closest('.drop-zone');
        $draggable.remove();
        if (originalParent.length) {
            this.updatePlaceholder(originalParent);
        }
        // Also remove from data structure (you'll need to implement this)
        this.removeFromStructure($draggable.data('id'));
    }
    
    // Add the new element
    switch(dragType) {
        case 'sub_div':
            this.addSubDiv(parentId);
            break;
        case 'social_section':
            this.addSocialSection(parentId);
            break;
        case 'field':
            this.addField(
                parentId,
                $draggable.data('field-type'),
                $draggable.data('field-label'),
                $draggable.data('field-html-type')
            );
            break;
        case 'social':
            this.addSocialButton(parentId, $draggable.data('social-provider'));
            break;
    }
}
    
makeSortable(containerId) {
    const $zone = $(`.drop-zone[data-parent="${containerId}"]`);
    
    if ($zone.hasClass('ui-sortable')) {
        $zone.sortable('destroy');
    }
    
    $zone.sortable({
        placeholder: 'sortable-placeholder',
        items: '.form-field, .sub-div, .social-section, .social-btn',
        handle: '.field-drag',
        cancel: '.container-controls button',
        update: () => this.autoSave()
    });
}
    
    addMainContainer() {
    const id = this.generateId('main');
    const html = Templates.mainContainer(id, this.settings.btn_color, this.settings.button_text);
    $('#formBuilder').append(html);
    
    this.containers[id] = {
        id: id,
        type: 'main',
        bgColor: '#ffffff',
        items: [],
        socialButtons: [],
        styles: {}
    };
    
    // Use the specific selector for main container
    this.makeDroppable(id, 'main');
    this.makeSortableForMain(id);
    this.autoSave();
    return id;
}

makeSortableForMain(containerId) {
    const $zone = $(`.main-container[data-id="${containerId}"] .main-drop-zone`);
    
    if ($zone.hasClass('ui-sortable')) {
        $zone.sortable('destroy');
    }
    
    $zone.sortable({
        placeholder: 'sortable-placeholder',
        items: '.form-field, .sub-div, .social-section',
        handle: '.field-drag',
        cancel: '.container-controls button',
        update: () => this.autoSave()
    });
}
    
    addSubDiv(parentId) {
        const id = this.generateId('sub');
        const html = Templates.subDiv(id);
        const $parentZone = $(`.drop-zone[data-parent="${parentId}"]`);
        
        $parentZone.append(html);
        this.addToStructure(parentId, { id: id, type: 'sub', bgColor: '#f8f9fa', items: [] });
        this.makeDroppable(id, 'sub');
        this.makeSortable(id);
        this.updatePlaceholder($parentZone);
        this.autoSave();
        return id;
    }
    
    addSocialSection(parentId) {
        const id = this.generateId('social');
        const html = Templates.socialSection(id);
        const $parentZone = $(`.drop-zone[data-parent="${parentId}"]`);
        
        $parentZone.append(html);
        this.addToStructure(parentId, { id: id, type: 'social_section', bgColor: '#ffffff', socialButtons: [] });
        this.makeDroppable(id, 'social');
        this.updatePlaceholder($parentZone);
        this.autoSave();
        return id;
    }
    
    addField(parentId, fieldType, label, htmlType) {
        const id = this.generateId('field');
        const placeholder = `Enter ${label.toLowerCase()}`;
        const html = Templates.field(id, fieldType, label, htmlType, placeholder, this.settings.show_labels);
        const $parentZone = $(`.drop-zone[data-parent="${parentId}"]`);
        
        $parentZone.append(html);
        this.addToStructure(parentId, {
            id: id,
            type: 'field',
            fieldType: fieldType,
            label: label,
            htmlType: htmlType,
            placeholder: placeholder,
            required: false
        });
        
        this.initFieldEvents(id);
        this.updatePlaceholder($parentZone);
        this.autoSave();
        return id;
    }
    
    addSocialButton(parentId, provider) {
        const socialData = MyLoginDesigner.social_providers[provider];
        const html = Templates.socialButton(provider, socialData.color, socialData.label);
        const $parentZone = $(`.drop-zone[data-parent="${parentId}"]`);
        
        $parentZone.append(html);
        this.addToStructure(parentId, { provider: provider }, 'socialButtons');
        this.updatePlaceholder($parentZone);
        this.autoSave();
    }
    
    generateId(prefix) {
        return `${prefix}_${Date.now()}_${++this.elementCounter}`;
    }
    
    addToStructure(parentId, item, arrayType = 'items') {
        for (let id in this.containers) {
            if (this.containers[id].id === parentId) {
                this.containers[id][arrayType].push(item);
                return;
            }
            this.findAndAddToNested(this.containers[id].items, parentId, item, arrayType);
        }
    }
    
    findAndAddToNested(items, parentId, item, arrayType) {
        for (let i = 0; i < items.length; i++) {
            if (items[i].id === parentId) {
                items[i][arrayType].push(item);
                return true;
            }
            if (items[i].items && this.findAndAddToNested(items[i].items, parentId, item, arrayType)) {
                return true;
            }
        }
        return false;
    }
    
    updatePlaceholder($zone) {
        if ($zone.children('.form-field, .sub-div, .social-section, .social-btn').length === 0) {
            if ($zone.children('.container-placeholder').length === 0) {
                $zone.append(Templates.placeholder());
            }
        } else {
            $zone.children('.container-placeholder').remove();
        }
    }
    
    removeField(fieldId) {
        $(`.form-field[data-id="${fieldId}"]`).remove();
        this.removeFromStructure(fieldId);
        if (this.selectedElementId === fieldId) this.clearSelection();
        this.autoSave();
    }
    
    removeSubDiv(subId) {
        $(`.sub-div[data-id="${subId}"]`).remove();
        this.removeFromStructure(subId);
        if (this.selectedElementId === subId) this.clearSelection();
        this.autoSave();
    }
    
    removeContainer(containerId) {
        delete this.containers[containerId];
        $(`.main-container[data-id="${containerId}"]`).remove();
        if (Object.keys(this.containers).length === 0) {
            this.showEmptyState();
        }
        if (this.selectedElementId === containerId) this.clearSelection();
        this.autoSave();
    }
    
    removeFromStructure(id) {
        for (let containerId in this.containers) {
            if (this.removeNestedItem(this.containers[containerId].items, id)) return;
        }
    }
    
    removeNestedItem(items, id) {
        for (let i = 0; i < items.length; i++) {
            if (items[i].id === id) {
                items.splice(i, 1);
                return true;
            }
            if (items[i].items && this.removeNestedItem(items[i].items, id)) return true;
        }
        return false;
    }
    
    selectElement(id, type, name) {
        $('.form-field, .sub-div, .main-container, .social-section').removeClass('selected');
        $(`[data-id="${id}"]`).addClass('selected');
        $('#selectedItemName').text(name);
        $('#selectionInfo').show();
        this.selectedElementId = id;
        $(document).trigger('elementSelected', { id, type });
    }
    
    clearSelection() {
        $('.form-field, .sub-div, .main-container, .social-section').removeClass('selected');
        $('#selectionInfo').hide();
        this.selectedElementId = null;
    }
    
    initFieldEvents(fieldId) {
        const $field = $(`.form-field[data-id="${fieldId}"]`);
        
        $field.on('click', (e) => {
            if (!$(e.target).hasClass('field-remove') && !$(e.target).hasClass('field-edit') && !$(e.target).hasClass('field-drag')) {
                e.stopPropagation();
                this.selectElement(fieldId, 'field', $field.data('field-type'));
            }
        });
        
        $field.find('.field-label').on('dblclick', (e) => {
            e.stopPropagation();
            this.makeEditable($(e.target), fieldId, 'label');
        });
        
        $field.find('.field-input').on('dblclick', (e) => {
            e.stopPropagation();
            this.makeEditable($(e.target), fieldId, 'placeholder');
        });
    }
    
    makeEditable($element, fieldId, type) {
        const originalValue = type === 'label' ? $element.text().replace('✏️', '').trim() : $element.attr('placeholder');
        const $input = $('<input>', { type: 'text', value: originalValue, class: 'inline-editor' });
        
        $element.hide().after($input);
        $input.focus();
        
        $input.on('blur', () => {
            const newValue = $input.val();
            if (type === 'label') {
                $element.html(newValue + '<span class="edit-hint">✏️</span>');
                this.updateFieldProperty(fieldId, 'label', newValue);
            } else {
                $element.attr('placeholder', newValue);
                this.updateFieldProperty(fieldId, 'placeholder', newValue);
            }
            $input.remove();
            $element.show();
        });
        
        $input.on('keypress', (e) => { if (e.key === 'Enter') $input.blur(); });
    }
    
    updateFieldProperty(fieldId, property, value) {
        for (let id in this.containers) {
            this.updateNestedProperty(this.containers[id].items, fieldId, property, value);
        }
        this.autoSave();
    }
    
    updateNestedProperty(items, fieldId, property, value) {
        for (let item of items) {
            if (item.id === fieldId && item.type === 'field') {
                item[property] = value;
                return true;
            }
            if (item.items && this.updateNestedProperty(item.items, fieldId, property, value)) return true;
        }
        return false;
    }
    
    editFieldSettings(fieldId) {
        for (let id in this.containers) {
            const field = this.findFieldInItems(this.containers[id].items, fieldId);
            if (field) {
                $('#editingFieldId').val(fieldId);
                $('#fieldPlaceholder').val(field.placeholder || '');
                $('#fieldRequired').prop('checked', field.required || false);
                $('#fieldSettingsModal').show();
                break;
            }
        }
    }
    
    saveFieldSettings() {
        const fieldId = $('#editingFieldId').val();
        const placeholder = $('#fieldPlaceholder').val();
        const required = $('#fieldRequired').is(':checked');
        
        for (let id in this.containers) {
            const field = this.findFieldInItems(this.containers[id].items, fieldId);
            if (field) {
                field.placeholder = placeholder;
                field.required = required;
                $(`.form-field[data-id="${fieldId}"] .field-input`).attr('placeholder', placeholder);
                if (required) {
                    $(`.form-field[data-id="${fieldId}"] .field-input`).attr('required', 'required');
                } else {
                    $(`.form-field[data-id="${fieldId}"] .field-input`).removeAttr('required');
                }
                break;
            }
        }
        $('#fieldSettingsModal').hide();
        this.autoSave();
    }
    
    findFieldInItems(items, fieldId) {
        for (let item of items) {
            if (item.id === fieldId && item.type === 'field') return item;
            if (item.items) {
                const found = this.findFieldInItems(item.items, fieldId);
                if (found) return found;
            }
        }
        return null;
    }
    
    updateSettings() {
        this.settings.btn_color = $('#btnColor').val();
        this.settings.button_text = $('#buttonText').val();
        this.settings.show_labels = $('#showLabels').is(':checked');
        $('.container-submit button').css('background', this.settings.btn_color).text(this.settings.button_text);
        this.autoSave();
    }
    
    autoSave() {
        if (this.currentFormId) this.saveForm();
    }
    
    saveForm() {
        const saveData = {
            form_id: this.currentFormId,
            containers: this.containers,
            settings: this.settings
        };
        
        $.ajax({
            url: MyLoginDesigner.ajax_url,
            type: 'POST',
            data: {
                action: 'my_login_save_form_settings',
                form_data: JSON.stringify(saveData),
                nonce: MyLoginDesigner.nonces.save_form
            },
            success: (r) => {
                if (r.success) {
                    this.saveHTMLFile();
                    this.saveCSSFile();
                    this.saveJSFile();
                }
            }
        });
    }
    
    saveHTMLFile() {
        $.ajax({
            url: MyLoginDesigner.ajax_url,
            type: 'POST',
            data: {
                action: 'my_login_save_form_html',
                form_id: this.currentFormId,
                form_key: this.currentFormKey,
                content: this.generateFormHTML(),
                nonce: MyLoginDesigner.nonces.save_html
            }
        });
    }
    
    saveCSSFile() {
        $.ajax({
            url: MyLoginDesigner.ajax_url,
            type: 'POST',
            data: {
                action: 'my_login_save_form_css',
                form_id: this.currentFormId,
                css_content: $('#customCSS').val(),
                nonce: MyLoginDesigner.nonces.save_css
            }
        });
    }
    
    saveJSFile() {
        $.ajax({
            url: MyLoginDesigner.ajax_url,
            type: 'POST',
            data: {
                action: 'my_login_save_form_js',
                form_id: this.currentFormId,
                js_content: $('#customJS').val(),
                nonce: MyLoginDesigner.nonces.save_js
            }
        });
    }
    
    loadForm(formId) {
        $.ajax({
            url: MyLoginDesigner.ajax_url,
            type: 'POST',
            data: {
                action: 'my_login_get_form',
                form_id: formId,
                nonce: MyLoginDesigner.nonces.get_form
            },
            success: (r) => {
                if (r.success && r.data) {
                    this.containers = r.data.containers || {};
                    this.settings = r.data.settings || this.settings;
                    this.currentFormKey = r.data.form_key;
                    this.renderFromData();
                    this.applySettingsToUI();
                }
            }
        });
    }
    
    renderFromData() {
        $('#formBuilder').empty();
        for (let id in this.containers) {
            this.renderContainerFromData(this.containers[id]);
        }
        if (Object.keys(this.containers).length === 0) this.showEmptyState();
    }
    
    renderContainerFromData(container) {
        $('#formBuilder').append(Templates.mainContainer(container.id, this.settings.btn_color, this.settings.button_text));
        this.makeDroppable(container.id, 'main');
        this.makeSortable(container.id);
        this.renderItemsFromData(container.id, container.items);
    }
    
    renderItemsFromData(parentId, items) {
        const $parentZone = $(`.drop-zone[data-parent="${parentId}"]`);
        
        for (let item of items) {
            if (item.type === 'sub') {
                $parentZone.append(Templates.subDiv(item.id, item.bgColor));
                this.makeDroppable(item.id, 'sub');
                this.makeSortable(item.id);
                if (item.items) this.renderItemsFromData(item.id, item.items);
            } else if (item.type === 'social_section') {
                $parentZone.append(Templates.socialSection(item.id));
                this.makeDroppable(item.id, 'social');
                if (item.socialButtons) {
                    item.socialButtons.forEach(btn => {
                        const provider = MyLoginDesigner.social_providers[btn.provider];
                        $parentZone.find(`.social-drop-zone[data-parent="${item.id}"]`).append(
                            Templates.socialButton(btn.provider, provider.color, provider.label)
                        );
                    });
                }
            } else if (item.type === 'field') {
                $parentZone.append(Templates.field(
                    item.id, item.fieldType, item.label, item.htmlType, 
                    item.placeholder, this.settings.show_labels, item.required
                ));
                this.initFieldEvents(item.id);
            }
        }
        this.updatePlaceholder($parentZone);
    }
    
    applySettingsToUI() {
        $('#btnColor').val(this.settings.btn_color);
        $('#buttonText').val(this.settings.button_text);
        $('#showLabels').prop('checked', this.settings.show_labels);
        $('.container-submit button').css('background', this.settings.btn_color).text(this.settings.button_text);
    }
    
    generateFormHTML() {
        let html = '';
        for (let id in this.containers) {
            html += this.renderContainerForOutput(this.containers[id]);
        }
        return html;
    }
    
    renderContainerForOutput(container) {
        let itemsHtml = '';
        for (let item of container.items) itemsHtml += this.renderItemForOutput(item);
        
        return `
            <div class="main-container" style="background: ${container.bgColor}; padding: 20px; border: 2px solid #667eea; border-radius: 16px; margin-bottom: 20px;">
                <div class="drop-zone">${itemsHtml || '<div class="container-placeholder">No fields added</div>'}</div>
                <div class="container-submit" style="text-align: center; margin-top: 20px;">
                    <button style="background: ${this.settings.btn_color}; padding: 12px 30px; border: none; border-radius: 8px; color: #fff; cursor: pointer;">${this.settings.button_text}</button>
                </div>
            </div>
        `;
    }
    
    renderItemForOutput(item) {
        if (item.type === 'field') {
            return this.renderFieldForOutput(item);
        } else if (item.type === 'sub') {
            let itemsHtml = '';
            for (let subItem of item.items) itemsHtml += this.renderItemForOutput(subItem);
            return `
                <div class="sub-div" style="background: ${item.bgColor}; padding: 15px; border: 2px solid #f5576c; border-radius: 12px; margin: 10px 0;">
                    ${itemsHtml || '<div class="container-placeholder">No fields added</div>'}
                </div>
            `;
        } else if (item.type === 'social_section') {
            let socialHtml = '';
            for (let social of item.socialButtons) {
                const provider = MyLoginDesigner.social_providers[social.provider];
                socialHtml += `<div class="social-btn" style="background: ${provider.color}; display: inline-block; padding: 8px 16px; margin: 5px; border-radius: 4px; color: #fff;">${provider.label}</div>`;
            }
            return `
                <div class="social-section" style="background: ${item.bgColor}; padding: 15px; border: 2px dashed #667eea; border-radius: 12px; margin: 10px 0;">
                    <div class="section-title" style="font-weight: bold; margin-bottom: 10px;">🔗 Social Login</div>
                    <div class="social-buttons">${socialHtml || '<div class="container-placeholder">No social buttons added</div>'}</div>
                </div>
            `;
        }
        return '';
    }
    
    renderFieldForOutput(field) {
        const showLabels = this.settings.show_labels;
        const isCheckbox = field.htmlType === 'checkbox';
        const isRadio = field.htmlType === 'radio';
        const requiredAttr = field.required ? 'required' : '';
        let inputHtml = Templates.getInputHtml(field.htmlType, field.id, field.placeholder, field.required, field.label);
        let labelHtml = '';
        if (showLabels && !isCheckbox && !isRadio) {
            labelHtml = `<label style="display: block; margin-bottom: 5px; font-weight: 500;">${field.label}</label>`;
        }
        return `<div class="form-field" style="margin-bottom: 12px;">${labelHtml}${inputHtml}</div>`;
    }
    
    clearAll() {
        if (confirm(MyLoginDesigner.strings.clear_confirm)) {
            this.containers = {};
            $('#formBuilder').html(Templates.emptyBuilder());
            this.initMainContainerDrop();
            this.clearSelection();
            this.autoSave();
        }
    }
    
    initTabs() {
        $('.tab-btn').on('click', function() {
            const tab = $(this).data('tab');
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.tab-content').removeClass('active');
            $(`#tab-${tab}`).addClass('active');
        });
    }
    
    initModals() {
        $('.close-modal, .cancel-modal').on('click', () => {
            $('#fieldSettingsModal, #containerSettingsModal, #createFormModal').hide();
        });
        $(window).on('click', (e) => { if ($(e.target).hasClass('modal')) $('.modal').hide(); });
    }
    
    initEvents() {
        $(document).on('click', '.remove-container', (e) => {
            const id = $(e.target).closest('.main-container').data('id');
            if (confirm('Remove this container?')) this.removeContainer(id);
        });
        $(document).on('click', '.remove-subdiv', (e) => {
            const id = $(e.target).closest('.sub-div').data('id');
            if (confirm('Remove this sub div?')) this.removeSubDiv(id);
        });
        $(document).on('click', '.field-remove', (e) => {
            const id = $(e.target).closest('.form-field').data('id');
            if (confirm('Remove this field?')) this.removeField(id);
        });
        $(document).on('click', '.field-edit', (e) => {
            const id = $(e.target).closest('.form-field').data('id');
            this.editFieldSettings(id);
        });
        $('#btnColor, #buttonText, #showLabels').on('change', () => this.updateSettings());
        $('#saveFormBtn').on('click', () => {
            if (this.currentFormId) { this.saveForm(); alert(MyLoginDesigner.strings.form_saved); }
            else alert(MyLoginDesigner.strings.select_form);
        });
        $('#saveCSSBtn').on('click', () => {
            if (this.currentFormId) this.saveCSSFile();
            else alert(MyLoginDesigner.strings.select_form);
        });
        $('#saveJSBtn').on('click', () => {
            if (this.currentFormId) this.saveJSFile();
            else alert(MyLoginDesigner.strings.select_form);
        });
        $('#clearAllBtn').on('click', () => this.clearAll());
        $('#clearSelectionBtn').on('click', () => this.clearSelection());
        $('#saveFieldSettingsBtn').on('click', () => this.saveFieldSettings());
        $('#createNewFormBtn').on('click', () => $('#createFormModal').show());
        $('#createFormSubmitBtn').on('click', () => this.createNewForm());
        $('#formList').on('click', '.form-item', (e) => {
            if (!$(e.target).closest('.form-actions').length) {
                this.loadForm($(e.target).closest('.form-item').data('form-id'));
            }
        });
        $('#formList').on('click', '.copy-shortcode', (e) => {
            e.stopPropagation();
            navigator.clipboard.writeText(`[my_login_form id="${$(e.target).closest('.copy-shortcode').data('form-id')}"]`);
            alert('Shortcode copied!');
        });
        $('#formList').on('click', '.duplicate-form', (e) => {
            e.stopPropagation();
            if (confirm(MyLoginDesigner.strings.duplicate_confirm)) {
                this.duplicateForm($(e.target).closest('.duplicate-form').data('form-id'));
            }
        });
        $('#formList').on('click', '.delete-form', (e) => {
            e.stopPropagation();
            if (confirm(MyLoginDesigner.strings.delete_confirm)) {
                this.deleteForm($(e.target).closest('.delete-form').data('form-id'));
            }
        });
        $('.preset-btn').on('click', (e) => this.applyPreset($(e.target).data('preset')));
    }
    
    createNewForm() {
        const formName = $('#newFormName').val();
        if (!formName) { alert('Enter form name'); return; }
        $.ajax({
            url: MyLoginDesigner.ajax_url,
            type: 'POST',
            data: {
                action: 'my_login_create_form',
                form_name: formName,
                form_type: $('#newFormType').val(),
                nonce: MyLoginDesigner.nonces.create_form
            },
            success: (r) => { if (r.success) location.reload(); else alert('Error creating form'); }
        });
    }
    
    duplicateForm(formId) {
        $.ajax({
            url: MyLoginDesigner.ajax_url,
            type: 'POST',
            data: { action: 'my_login_duplicate_form', form_id: formId, nonce: MyLoginDesigner.nonces.duplicate_form },
            success: (r) => { if (r.success) location.reload(); else alert('Error duplicating form'); }
        });
    }
    
    deleteForm(formId) {
        $.ajax({
            url: MyLoginDesigner.ajax_url,
            type: 'POST',
            data: { action: 'my_login_delete_form', form_id: formId, nonce: MyLoginDesigner.nonces.delete_form },
            success: (r) => { if (r.success) location.reload(); else alert('Error deleting form'); }
        });
    }
    
    applyPreset(preset) {
        const presets = { modern: { btn_color: '#667eea' }, minimal: { btn_color: '#000000' }, dark: { btn_color: '#4CAF50' } };
        const p = presets[preset];
        if (p) { $('#btnColor').val(p.btn_color); this.updateSettings(); }
    }
}

// ============================================
// INITIALIZE
// ============================================

$(document).ready(function() {
    window.builder = new FormBuilder();
});
})(jQuery);