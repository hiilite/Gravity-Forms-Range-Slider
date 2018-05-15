<?php
/*
Plugin Name: Gravity Forms Range Slider Add-On
Plugin URI: https://hiilite.com/product/gravity-forms-range-slider/
Description: Creates a Gravity Forms range slider field that allows users to pick a data range.
Version: 1.1.1
Author: Hiilite
Author URI: https://hiilite.com
Text Domain: gravityformsrangeslider

------------------------------------------------------------------------
Copyright 2009-2017 Hiilite, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

define( 'GF_RANGESLIDER_VERSION', '1.1.1' );
define( 'GF_RANGESLIDER_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'GF_RANGESLIDER_DIR_PATH', plugin_dir_path( __FILE__ ) );
require_once( 'includes/inline_scripts.php' );



/**
 * hii_rangeslider_gravity_form_check function.
 *
 * Do checks after plugins have loaded
 * 
 * @access public
 * @return void
 */
function hii_rangeslider_gravity_form_check() {

	// If Gravity Forms is not enabled and current user can activate_plugins disable Gravity Slider Fields and display notice.
	if ( !class_exists( 'GFForms' ) && !class_exists( 'GF_Fields' ) && current_user_can( 'activate_plugins' )) {

		add_action( 'admin_init', 'hii_rangeslider_plugin_deactivate' );
		add_action( 'admin_notices', 'hii_rangeslider_plugin_deactivate_admin_notice' );

		function hii_rangeslider_plugin_deactivate() {
			
			deactivate_plugins( plugin_basename( __FILE__ ) );

		} // end hii_rangeslider_plugin_deactivate

		function hii_rangeslider_plugin_deactivate_admin_notice() {
			
			echo '<div class="error"><p><strong>Gravity Range Slider Fields</strong> has been deactivated, as it requires Gravity Forms v1.9 or greater.</p></div>';
			if ( isset( $_GET['activate'] ) ) {

				unset( $_GET['activate'] );

			}

		} // end hii_rangeslider_plugin_deactivate_admin_notice

	}

} // end hii_rangeslider_gravity_form_check
add_action( 'plugins_loaded', 'hii_rangeslider_gravity_form_check' );






/**
 * hii_rangeslider_add_field_buttons function.
 * 
 * Reassign the slider field button to advanced group
 *
 * @access public
 * @param mixed $field_groups
 * @return void
 */
function hii_rangeslider_add_field_buttons( $field_groups ) {

	// Loop through field groups
	foreach( $field_groups as &$group ) {
		
		$rangeslider = array(
			'class'		=> 'button',
			'data-type'	=> 'rangeslider',
			'value'		=> __('Range Slider', 'gravityforms'),
			'onclick'	=> "StartAddField('rangeslider');"
		);
		
		// Find advanced group
		if( 'advanced_fields' == $group['name'] ) {

			// Add slider field
			$group['fields'][] = $rangeslider;
			break;

		}

	}

	return $field_groups;

} // end hii_rangeslider_add_field_buttons
add_filter( 'gform_add_field_buttons', 'hii_rangeslider_add_field_buttons' );



/**
 * hii_rangeslider_title function.
 * 
 * @access public
 * @param mixed $type
 * @return void
 */
function hii_rangeslider_title( $type ) {
	if ( $type == 'rangeslider' )
		return __( 'Range Slider' , 'gravityforms' );
}
add_filter( 'gform_field_type_title' , 'hii_rangeslider_title' );


/**
 * hii_rangeslider_field_input function.
 * 
 *	Front end display of field
 *
 * @access public
 * @param mixed $input
 * @param mixed $field
 * @param mixed $value
 * @param mixed $lead_id
 * @param mixed $form_id
 * @return void
 */
function hii_rangeslider_field_input ( $input, $field, $value, $lead_id, $form_id ){
	
	if($field['type'] == 'rangeslider'):
	  
		
		if(rgpost( 'input_'.$field['id'].'_1') != null){
			$value = array();
			$value['1'] = rgpost( 'input_'.$field['id'].'_1' );
			$value['2'] = rgpost( 'input_'.$field['id'].'_2' );
		}
		

		$script = generate_slider_script($field, $value);
		$html = generate_slider_html($field, $value);
		if(is_admin()) $html .= "<script>$script</script>";
		else wp_add_inline_script('noUiSlider'.$field['id'], $script, 'after');
		
		return $html;
	endif;

}
add_action( "gform_field_input" , "hii_rangeslider_field_input", 10, 5 );




/**
 * enable_total_in_conditional_logic function.
 * 
 * @access public
 * @param mixed $form
 * @return void
 */
function enable_rangeslider_in_conditional_logic( $form ) {
    if ( GFCommon::is_entry_detail() ) {
        return $form;
    }
?><script type='text/javascript'>
          gform.addFilter('gform_is_conditional_logic_field', function (isConditionalLogicField, field) {
              return field.type == 'rangeslider' ? true : isConditionalLogicField;
           });
          gform.addFilter('gform_conditional_logic_operators', function (operators, objectType, fieldId) {
               var targetField = GetFieldById(fieldId);
              if (targetField && targetField['type'] == 'rangeslider') {
                  operators = {
	                  '>': 'greaterThan', 
	                  '<': 'lessThan',
	                  'is': 'is',
	              };
               }
               return operators;
           });
    
    	gform.addFilter('gform_conditional_logic_fields', function (options, form, selectedFieldId) {
            options = [];
             
            var currentField = jQuery('.field_selected'),
                currentFieldId = currentField.length == 1 ? currentField[0].id.substr(6) : 0;
			
            for (var f = 0; f < form.fields.length; f++) {
 
				var field = form.fields[f];
				
                if (IsConditionalLogicField(field)) {
                    
                    if( field.inputs && jQuery.inArray( GetInputType( field ), [ 'checkbox', 'email', 'rangeslider' ] ) == -1 ) {
	                    
                        for (var j = 0; j < field.inputs.length; j++) {
	                        var input = field.inputs[j];
                            if( ! input.isHidden ) {
		                        options.push( {
		                            label: GetLabel( field, input.id ),
		                            value: input.id
		                        } );
		                    }
                        }
                        
                    } else {
	                    if( GetInputType( field ) == 'rangeslider') {
		                    options.push( {
	                            label: GetLabel( field ) + ' Min',
	                            value: field.id + '_1'
	                        } );
	                        options.push( {
	                            label: GetLabel( field ) + ' Max',
	                            value: field.id + '_2'
	                        } );
			                  
	                    } else  {
		                	options.push({
	                            label: GetLabel( field ),
								value: field.id
	                        }); 
		                }
                        
                    }
                }
 
            }
			
            // get entry meta fields and append to existing fields
            jQuery.merge(options, GetEntryMetaFields(selectedFieldId));
            return options;
        });	</script>
	<?php
    return $form;
}
add_filter( 'gform_admin_pre_render', 'enable_rangeslider_in_conditional_logic' );





/**
 * hii_rangeslider_inline_js function.
 * 
 * @access public
 * @param mixed $form
 * @return void
 */
function hii_rangeslider_inline_js($form){
	$script = '';
	foreach($form['fields'] as $field) {
   		if($field['type'] == 'rangeslider'){
   			$script .= generate_slider_script($field);
		}
	}
	GFFormDisplay::add_init_script( $form['id'], 'rangeslider', GFFormDisplay::ON_PAGE_RENDER, $script );
	
}
add_action( 'gform_register_init_scripts', 'hii_rangeslider_inline_js' );



/**
 * hii_rangeslider_set_defaults function.
 * 
 * Set default values when adding a slider
 *
 * @access public
 * @return void
 */
function hii_rangeslider_set_defaults() {
	?>
	    case "rangeslider" :
	    	field.label = "Range Slider";
	        field.numberFormat = "decimal_dot";
	        field.sliderType = '';
	        field.rangeMin = 0;
	        field.rangeMax = 10;
	        field.rangeslider_step = 1;
	        field.defaultMin = 1;
	        field.defaultMax = 9;
	        field.prefix = '';
	        field.betweenText = '-';
	        field.postfix = '';
	        field.decimals = 0;
	        field.showTooltip = false;
	        field.showTextDisplay = false;
	        field.required = false;
	        field.thousand = ',';
	        field.sliderDirection = 'ltr';

	    break;
	<?php
} // end hii_rangeslider_set_defaults
add_action( 'gform_editor_js_set_default_values', 'hii_rangeslider_set_defaults' );


/*
*
*	Execute javascript for proper loading of field
*
*/

/**
 * hii_rangeslider_editor_js function.
 * 
 * @access public
 * @return void
 */
function hii_rangeslider_editor_js(){
	?>
		<script type='text/javascript'>
				// Bind to the load field settings event to initialize the slider settings
			jQuery(document).ready(function($) {
				fieldSettings.rangeslider += ', .rangeslider_settings, .label_setting, .description_setting, .admin_label_setting, .css_class_setting, .visibility_setting, .conditional_logic_field_setting, .css_class_setting, .prepopulate_field_setting, .rules_setting, .admin_label_setting, .duplicate_setting';
				
				jQuery(document).bind("gform_load_field_settings", function(event, field, form){
					jQuery("#sliderType").attr("checked", field.sliderType == true);
					jQuery("#rangeMin").val(field.rangeMin);
					jQuery("#rangeMax").val(field.rangeMax);
					jQuery("#rangeslider_step").val(field.rangeslider_step);
					jQuery("#defaultMin").val(field.defaultMin);
					jQuery("#defaultMax").val(field.defaultMax);
					jQuery("#prefix").val(field.prefix);
					jQuery("#betweenText").val(field.betweenText);
					jQuery("#postfix").val(field.postfix);
					jQuery("#decimals").val(field.decimals);
					jQuery("#showTooltip").attr("checked", field.showTooltip == true);
					jQuery("#showTextDisplay").attr("checked", field.showTextDisplay == true);
					jQuery("#required").attr("checked", field.required == true);
					jQuery("#thousand").val(field.thousand);
					jQuery("#sliderDirection").val(field.sliderDirection);
				});
			});
			
		</script>
	<?php
} // end hii_rangeslider_editor_js
add_action( 'gform_editor_js', 'hii_rangeslider_editor_js' );


/**
 * add_merge_tags function.
 * 
 * @access public
 * @param mixed $form
 * @return void
 */
function add_merge_tags( $form ) {
	
	$rangeslider_fields = GFAPI::get_fields_by_type($form, array('rangeslider'));
	
	if(empty($rangeslider_fields)) return $form;
	
	$merge_tags = array();
	foreach ( $rangeslider_fields as $field) {
		$field_id = $field->id;
		$field_label = $field->label;
		$group = $field->isRequired ? 'required' : 'optional';
		$merge_tags[] = array( 'group' => $group, 'label' => __('Rangeslider: ').$field_label.' Min', 'tag' => "{rangeslider:{$field_id}.1}");
		$merge_tags[] = array( 'group' => $group, 'label' => __('Rangeslider: ').$field_label.' Max', 'tag' => "{rangeslider:{$field_id}.2}");
		
	}
    ?>
    <script type="text/javascript">
	    
	    gform.addFilter("gform_merge_tags", "rangeslider_add_merge_tags");
        function rangeslider_add_merge_tags(mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option){
	        console.log(mergeTags, isPrepop); 
	       
	        var sliderMergeTags = <?=json_encode($merge_tags);?>;
	        jQuery.each(sliderMergeTags, function (i, sliderMergeTag) {
		        mergeTags[sliderMergeTag.group].tags.push({ tag: sliderMergeTag.tag, label: sliderMergeTag.label });
	        });
	                    
            return mergeTags;
        }
        
        // hacky, but only temporary
        jQuery(document).ready(function($){
	         var calcMergeTagSelect = $('#field_calculation_formula_variable_select');
	        var sliderMergeTags = <?=json_encode($merge_tags);?>;
	        jQuery.each(sliderMergeTags, function (i, sliderMergeTag) {	
		        calcMergeTagSelect.find('optgroup').eq(0).append( '<option value="'+sliderMergeTag.tag+'">'+sliderMergeTag.label+'</option>' );
	        });
	        
           
        });
        
       
    </script>
    <?php
    //return the form object from the php hook  
    return $form;
}
add_filter( 'gform_admin_pre_render', 'add_merge_tags' );

/*
*
// Render custom options for the field
*
*/

/**
 * hii_range_slider_settings function.
 * 
 * @access public
 * @param mixed $position
 * @param mixed $form_id
 * @return void
 */
function hii_range_slider_settings( $position, $form_id ) {

	// TODO: Change to use dual slider values
	if ( $position == 0) :
		?>
			<li class="rangeslider_value_type field_setting rangeslider_settings">
				<label class="section_label" style="clear:both;">
					<?php _e( 'Slider Type', 'rangeslider-locale' ); ?>
					<?php gform_tooltip( 'rangeslider_value_type' ); ?>
				</label>
				<div style="width:37%;float:left">
					<input type="checkbox" id="sliderType" style="" onchange="SetFieldProperty('sliderType', this.checked);" />
					<label for="sliderType" style="display:inline-block;vertical-align:text-top;" for="sliderType"><?php _e( 'Single Slider', 'rangeslider-locale' ); ?></label>
				</div>
				
			</li>
			<li class="rangeslider_value_relations field_setting rangeslider_settings">
				<label style="clear:both;" class="section_label">
					<?php _e( 'Value Range', 'rangeslider-locale' ); ?>
					<?php gform_tooltip( 'rangeslider_value_relations' ); ?>
				</label>
				<div style="width:33%;float:left">
					<input type="number" id="rangeMin" style="width:100%;" onchange="SetFieldProperty('rangeMin', this.value);" />
					<label for="rangeMin"><?php _e( 'Min', 'rangeslider-locale' ); ?></label>
				</div>
				
				<div style="width:33%;float:left">
					<input type="number" id="rangeslider_step" step=".01" style="width:100%;" onchange="SetFieldProperty('rangeslider_step', this.value);" />
					<label for="rangeslider_step"><?php _e( 'Step', 'rangeslider-locale' ); ?></label>
				</div>
				
				<div style="width:33%;float:left">
					<input type="number" id="rangeMax" style="width:100%;" onchange="SetFieldProperty('rangeMax', this.value);" />
					<label for="rangeMax"><?php _e( 'Max', 'rangeslider-locale' ); ?></label>
				</div>
				<br class="clear">
			</li>
			
			<li class="rangeslider_value_defaults field_setting rangeslider_settings">
				<label style="clear:both;" class="section_label">
					<?php _e( 'Range Defaults', 'rangeslider-locale' ); ?>
					<?php gform_tooltip( 'rangeslider_value_relations' ); ?>
				</label>
				<div style="width:50%;float:left">
					<input type="text" id="defaultMin" style="width:100%;" onchange="SetFieldProperty('defaultMin', this.value);" />
					<label for="defaultMin"><?php _e( 'Start', 'rangeslider-locale' ); ?></label>
				</div>
				<div style="width:50%;float:left">
					<input type="text" id="defaultMax" style="width:100%;" onchange="SetFieldProperty('defaultMax', this.value);" />
					<label for="defaultMax"><?php _e( 'End', 'rangeslider-locale' ); ?></label>
				</div>
				<br class="clear">
			</li>
			
			<li class="rangeslider_text_values field_setting rangeslider_settings">
				<label style="clear:both;" class="section_label">
					<?php _e( 'Formatting', 'rangeslider-locale' ); ?>
				</label>
				<div style="width:25%;float:left">
					<input type="text" id="prefix" style="width:100%;" onchange="SetFieldProperty('prefix', this.value);" />
					<label for="prefix"><?php _e( 'Prefix', 'rangeslider-locale' ); ?></label>
				</div>
				
				<div style="width:25%;float:left">
					<input type="text" id="betweenText" step=".01" style="width:100%;" onchange="SetFieldProperty('betweenText', this.value);" />
					<label for="betweenText"><?php _e( 'Seperator', 'rangeslider-locale' ); ?></label>
				</div>
				
				<div style="width:25%;float:left">
					<input type="text" id="postfix" style="width:100%;" onchange="SetFieldProperty('postfix', this.value);" />
					<label for="postfix"><?php _e( 'Postfix', 'rangeslider-locale' ); ?></label>
				</div>
				
				<div style="width:25%;float:left">
					<input type="text" id="thousand" style="width:100%;" onchange="SetFieldProperty('thousand', this.value);" />
					<label for="thousand"><?php _e( 'Thousands Sep.', 'rangeslider-locale' ); ?></label>
				</div>
				<br class="clear">
			</li>
			
			<li class="rangeslider_text_values field_setting rangeslider_settings">
				<label style="clear:both;" class="section_label">
					<?php _e( 'Extras', 'rangeslider-locale' ); ?>
				</label>
				<div style="width:37%;float:left">
					<input type="checkbox" id="showTooltip" style="" onchange="SetFieldProperty('showTooltip', this.checked);" />
					<label for="showTooltip" style="display:inline-block;vertical-align:text-top;"><?php _e( 'Show Tooltip', 'rangeslider-locale' ); ?></label>
				</div>
				
				<div style="width:37%;float:left">
					<input type="checkbox" id="showTextDisplay"  style="" onchange="SetFieldProperty('showTextDisplay', this.checked);" />
					<label for="showTextDisplay" style="display:inline-block;vertical-align:text-top;"><?php _e( 'Show Text Display', 'rangeslider-locale' ); ?></label>
				</div>
				
				<div style="width:25%;float:left">
					<input type="number" id="decimals" style="width:100%;" onchange="SetFieldProperty('decimals', this.value);" />
					<label for="decimals" ><?php _e( 'Decimals', 'rangeslider-locale' ); ?></label>
				</div>
				
				<br class="clear">
			</li>
		<?php
	endif; // position 0
} // end hii_range_slider_settings
add_action( 'gform_field_standard_settings' , 'hii_range_slider_settings' , 10, 2 );

function hii_range_slider_appearance_settings($position, $form_id){
	
	if( $position == 0):
		?>
		<li class="rangeslider_value_direction field_setting rangeslider_settings">
			<label style="clear:both;" class="section_label">
				<?php _e( 'Direction', 'rangeslider-locale' ); ?>
				<?php gform_tooltip( 'rangeslider_value_direction' ); ?>
			</label>
			<div style="">
				<select id="sliderDirection" style="" onchange="SetFieldSliderDirection(this);">
					<option value="ltr"><?php _e( 'Left-to-Right');?></option>
					<option value="rtl"><?php _e( 'Right-to-Left');?></option>
				</select>
			</div>
			
		</li>
		<?php
	endif; // position 0
	
}
add_action( 'gform_field_appearance_settings' , 'hii_range_slider_appearance_settings', 10, 2);

/**
 * hii_rangeslider_tooltips function.
 *
 * Add tooltips for the custom options
 * 
 * @access public
 * @param mixed $tooltips
 * @return void
 */
function hii_rangeslider_tooltips( $tooltips ) {
	$tooltips['rangeslider_value_relations'] = __( '<h6>Value Relations</h6>Enter descriptive terms that relate to the min and max number values of the slider. (End value not used in Single Slider)', 'rangeslider-locale' );
	$tooltips['rangeslider_step'] = __( '<h6>Step</h6>Enter a value that each interval or step will take between the min and max of the slider. The full specified value range of the slider (max - min) should be evenly divisible by the step and the step should not exceed a precision of two decimal places. (default: 1)', 'rangeslider-locale' );
	$tooltips['rangeslider_value_visibility'] = __( '<h6>Value Visibility</h6>Select whether to hide, show on hover & drag, or always show the currently selected value.', 'rangeslider-locale' );
	
		$tooltips['rangeslider_value_type'] = __( '<h6>Slider Type</h6>Select whether to use a single slider or a range slider.', 'rangeslider-locale' );
		
		$tooltips['rangeslider_value_required'] = __( '<h6>Use For Calculations</h6>Allows values to be used in calculations.', 'rangeslider-locale' );
		$tooltips['rangeslider_value_direction'] = __( '<h6>Change Direction</h6>Reverses the handles and the text direction', 'rangeslider-locale' );
	
	return $tooltips;

} // end hii_rangeslider_tooltips
add_filter( 'gform_tooltips', 'hii_rangeslider_tooltips');



// Enqueue all scripts and styles

/**
 * rangeslider_enqueue function.
 * 
 * @access public
 * @return void
 */
function rangeslider_enqueue() {

	// Enqueue the styles
	wp_enqueue_style( 'noUiSlider', GF_RANGESLIDER_DIR_URL . 'js/noUiSlider/nouislider.min.css', array(), GF_RANGESLIDER_VERSION );
	wp_enqueue_style( 'gravityfroms_rangeslider', GF_RANGESLIDER_DIR_URL . 'css/gravityfroms_rangeslider.css', array(), GF_RANGESLIDER_VERSION );

	// Enqueue necessary scripts
	wp_enqueue_script( 'noUiSlider', GF_RANGESLIDER_DIR_URL . 'js/noUiSlider/nouislider.js', array( 'jquery' ), GF_RANGESLIDER_VERSION );
	wp_enqueue_script( 'wnumb', GF_RANGESLIDER_DIR_URL . 'js/wnumb/wNumb.js', array( 'jquery' ), GF_RANGESLIDER_VERSION );
	wp_enqueue_script( 'rangeslider_form_editor', GF_RANGESLIDER_DIR_URL . 'js/rangeslider_form_editor.js', array( 'jquery' ), GF_RANGESLIDER_VERSION );

} // end rangeslider_enqueue







/**
 * hii_rangelider_enqueue_scripts function.
 * 
 * Add our scripts and styles if a slider field exists in the form
 *
 * @access public
 * @param mixed $form
 * @param mixed $is_ajax
 * @return void
 */
function hii_rangelider_enqueue_scripts( $form, $is_ajax ) {

	// Loop through form fields
	foreach ( $form['fields'] as $field ) {
		
		// If a rangeslider is found
		if ( 'rangeslider' == $field['type'] ) {

			rangeslider_enqueue();

			// Then stop looking through the fields
			break;

		}

	}

} // end hii_rangelider_enqueue_scripts
add_action( 'gform_enqueue_scripts' , 'hii_rangelider_enqueue_scripts', 10, 2 );



/**
 * hii_rangeslider_admin_enqueue_scripts function.
 *
 * Add our scripts and styles if a slider field exists in the form
 * 
 * @access public
 * @return void
 */
function hii_rangeslider_admin_enqueue_scripts() {

	if ( isset($_GET['page']) && 'gf_edit_forms' == $_GET['page'] ) {

		rangeslider_enqueue();

	}

} // end hii_rangeslider_admin_enqueue_scripts
add_action( 'admin_enqueue_scripts', 'hii_rangeslider_admin_enqueue_scripts' );


/**
 * hii_rangeslider_register_safe_script function.
 * 
 * @access public
 * @param mixed $scripts
 * @return void
 */
function hii_rangeslider_register_safe_script( $scripts ){

    //registering my script with Gravity Forms so that it gets enqueued when running on no-conflict mode
    $scripts[] = 'noUiSlider';
    $scripts[] = 'wnumb';
    $scripts[] = 'rangeslider_form_editor';
    
    return $scripts;

} // end hii_rangeslider_register_safe_script
add_filter( 'gform_noconflict_scripts', 'hii_rangeslider_register_safe_script' );


/**
 * hii_rangeslider_register_safe_styles function.
 * 
 * @access public
 * @param mixed $styles
 * @return void
 */
function hii_rangeslider_register_safe_styles( $styles ){

    //registering my script with Gravity Forms so that it gets enqueued when running on no-conflict mode
    $styles[] = 'noUiSlider';
    $styles[] = 'gravityfroms_rangeslider';
    
    return $styles;

} // end hii_rangeslider_register_safe_script
add_filter( 'gform_noconflict_styles', 'hii_rangeslider_register_safe_styles' );


/**
 * hii_rangeslider_custom_class function.
 * 
 *	Add a custom class to the field li
 *
 * @access public
 * @param mixed $classes
 * @param mixed $field
 * @param mixed $form
 * @return void
 */
function hii_rangeslider_custom_class($classes, $field, $form){

	if( $field["type"] == "rangeslider" ){
		$classes .= " gform_rangeslider";
	}
	
	return $classes;
}
add_action("gform_field_css_class", "hii_rangeslider_custom_class", 10, 3);



function hii_rangeslider_save_field_value(  $submitted_values, $form) {
    
    return $submitted_values;
}
add_action( 'gform_submission_values_pre_save', 'hii_rangeslider_save_field_value', 10, 2 );


/**
 * hii_rangeslider_pre_submission_filter function.
 * 
 * Append min/max relation notes to label in notifications, confirmations and entry detail
 *
 * @access public
 * @param mixed $form
 * @return void
 */
function hii_rangeslider_pre_submission_filter( $form ) {

	// Loop through form fields
	foreach ( $form['fields'] as &$field ) {
		
		// If a slider is found
		if ( 'rangeslider' == $field['type'] ) {

			// Set default min/max values, if they do not exist for the field
			$min = ( isset( $field['rangeMin'] ) && '' != $field['rangeMin'] ) ? $field['rangeMin'] : 0;
			$max = ( isset( $field['rangeMax'] ) && '' != $field['rangeMax'] ) ? $field['rangeMax'] : 10;

			// If min/max relations exist, append them to the field label
			if ( '' != $field['rangeslider_min_value_relation'] || '' != $field['rangeslider_max_value_relation'] ) {

				$field['label'] = $field['label'] . ' (' . GFCommon::format_number( $min, $field['numberFormat'] ) . ': ' . $field['rangeslider_min_value_relation'] . ', ' . GFCommon::format_number( $max, $field['numberFormat'] ) . ': ' . $field['rangeslider_max_value_relation'] . ')';

			}

		}

	}

	return $form;

} // end hii_rangeslider_pre_submission_filter
add_filter( 'gform_pre_submission_filter', 'hii_rangeslider_pre_submission_filter' );
( isset( $_GET['page'] ) && 'gf_entries' == $_GET['page'] ) ? add_filter( 'gform_admin_pre_render', 'hii_rangeslider_pre_submission_filter' ) : FALSE;
?>