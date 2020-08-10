jQuery(document).ready(function ($) {


});
function populate_pod_fields(elem,form_slug,field_id){

    if(buddyformsGlobal[form_slug] && elem && field_id !==''){
        var selected_pod = elem.value;
        var selected_pod_items=  buddyformsGlobal[form_slug]['pod_form_fields'][selected_pod];
         var element_select_pods_item = jQuery('select[data-pods-field-id="' + field_id + '"]');
         element_select_pods_item.find('option')
             .remove()
             .end();
        jQuery.each(selected_pod_items, function( index, description ) {
            element_select_pods_item.append(new Option(description,index))
        });
        element_select_pods_item.change();
    }
}

function change_slug_and_name(elem,form_slug,field_id){

    if(buddyformsGlobal[form_slug] && elem && field_id !==''){
        var selected_pod = elem.value;

        var element_slug_pods_item = jQuery('input[data-pods-field-slug="' + field_id + '"]');
        var element_name_pods_item = jQuery('input[data-pods-field-name="' + field_id + '"]');

        element_slug_pods_item.val(selected_pod);
        element_name_pods_item.val('PODS Field: '+selected_pod);

    }
}
