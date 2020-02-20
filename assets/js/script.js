function BuddyFormsPods() {
    return {
        init: function () {
            if (BuddyFormsHooks && buddyformsGlobal && jQuery(document).Pods) {

                BuddyFormsHooks.addFilter('buddyforms:field:name', function (fieldName, [formSlug, fieldId, form]) {
                    if (fieldName && formSlug && buddyformsGlobal && buddyformsGlobal[formSlug] && buddyformsGlobal[formSlug].form_fields) {
                        if (buddyformsGlobal[formSlug].form_fields[fieldName]) {
                            return buddyformsGlobal[formSlug].form_fields[fieldName].name;
                        }
                    }

                    return fieldName;
                }, 10);

                BuddyFormsHooks.addAction('buddyforms:submit', function ([currentForm, event]) {
                    if ('object' == typeof tinymce) {
                        tinymce.triggerSave();
                        var podsTinyMCE = jQuery(currentForm).find('.pods-ui-field-tinymce').find('textarea.wp-editor-area');
                        if (podsTinyMCE && podsTinyMCE.length > 0) {
                            jQuery.each(podsTinyMCE, function () {
                                var currentEditorID = jQuery(this).attr('id');
                                var currentEditor = tinymce.get(currentEditorID);
                                if (currentEditor && currentEditor.settings) {
                                    currentEditor.settings.add_form_submit_trigger = false;
                                    var content = currentEditor.getContent();
                                    if (content) {
                                        jQuery(this).val(content);
                                    }
                                }
                            });
                        }
                    }
                }, 50);

                jQuery(document).Pods('dependency');
            }
        }
    }
}

var fncBuddyFormsPods = BuddyFormsPods();
jQuery(document).ready(function () {
    fncBuddyFormsPods.init();
});
