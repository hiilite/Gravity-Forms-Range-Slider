<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GFRangeslider extends GF_Field {
	
	public $type = 'rangeslider';

	public function get_form_editor_field_title() {
		return __( 'Range Slider', 'gravityforms' );
	}
	
	function get_form_editor_field_settings() {
		return array(
			'label_setting',
			'description_setting',
			'number_format_setting',
			'range_setting',
			'label_placement_setting',
			'css_class_setting',
			'admin_label_setting',
			'default_value_setting',
			'visibility_setting',
			'prepopulate_field_setting',
			'conditional_logic_field_setting',
			'rangeslider_value_relations',
			'rangeslider_step',
			'rangeslider_value_visibility',
			'size_setting',
			'rules_setting',
			//'number_format_setting',
			//'calculation_setting',
			'error_message_setting',
			'label_placement_setting',
			'admin_label_setting',
			'duplicate_setting',
		);
		
	}

	public function is_conditional_logic_supported(){
		return true;
	}
	
	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		$value = $this->get_input_value_submission( 'input_' . $this->id, $this->inputName, $field_values, $get_from_post_global_var );
		$value = trim( $value );
		if ( $this->numberFormat == 'currency' ) {
			require_once( GFCommon::get_base_path() . '/currency.php' );
			$currency = new RGCurrency( GFCommon::get_currency() );
			$value    = $currency->to_number( $value );
		} else if ( $this->numberFormat == 'decimal_comma' ) {
			$value = GFCommon::clean_number( $value, 'decimal_comma' );
		} else if ( $this->numberFormat == 'decimal_dot' ) {
			$value = GFCommon::clean_number( $value, 'decimal_dot' );
		}

		return $value;
	}
	
	
	public function validate( $value, $form ) {

		// the POST value has already been converted from currency or decimal_comma to decimal_dot and then cleaned in get_field_value()

		$value     = GFCommon::maybe_add_leading_zero( $value );
		$raw_value = rgar($_POST, 'input_' . $this->id, '');
		$raw_value_min = rgar($_POST, 'input_' . $this->id, '_min');
		$raw_value_max = rgar($_POST, 'input_' . $this->id, '_min'); 
		//Raw value will be tested against the is_numeric() function to make sure it is in the right format.
		
		$requires_valid_number = ! rgblank( $raw_value ) && ! $this->has_calculation();
		
		$raw_value       = GFCommon::maybe_add_leading_zero( $raw_value );
		$is_valid_number = $this->validate_range( $value ) && GFCommon::is_numeric( $raw_value, $this->numberFormat );

		if ( $requires_valid_number && ! $is_valid_number ) {
			$this->failed_validation  = true;
			$this->validation_message = empty( $this->errorMessage ) ? $this->get_range_message() : $this->errorMessage;
		} 
		

	}
	
	
	/**
	 * Validates the range of the number according to the field settings.
	 *
	 * @param array $value A decimal_dot formatted string
	 *
	 * @return true|false True on valid or false on invalid
	 */
	private function validate_range( $value ) {

		if ( ! GFCommon::is_numeric( $value, 'decimal_dot' ) ) {
			return false;
		}
		
		$numeric_min = $this->numberFormat == 'decimal_comma' ? GFCommon::clean_number( $this->rangeMin, 'decimal_comma' ) : $this->rangeMin;
		$numeric_max = $this->numberFormat == 'decimal_comma' ? GFCommon::clean_number( $this->rangeMax, 'decimal_comma' ) : $this->rangeMax;

		if ( ( is_numeric( $this->rangeMin ) && $value < $this->rangeMin ) ||
			( is_numeric( $this->rangeMax ) && $value > $this->rangeMax )
		) {
			return false;
		} else {
			return true;
		}
	}
	
	
	/**
	 * get_range_message function.
	 * 
	 * @access public
	 * @return void
	 */
	public function get_range_message() {
		$min     = $this->rangeMin;
		$max     = $this->rangeMax;
		
		$numeric_min = $min;
		$numeric_max = $max;

		if( $this->numberFormat == 'decimal_comma' ){
			$numeric_min = empty( $min ) ? '' : GFCommon::clean_number( $min, 'decimal_comma', '');
			$numeric_max = empty( $max ) ? '' : GFCommon::clean_number( $max, 'decimal_comma', '');
		}

		$message = '';
		
		if ( is_numeric( $min ) && is_numeric( $max ) ) {
			$message = sprintf( __( 'Please enter a value between %s and %s.', 'gravityforms' ), "<strong>$min</strong>", "<strong>$max</strong>" );
		} else if ( is_numeric( $min ) ) {
			$message = sprintf( __( 'Please enter a value greater than or equal to %s.', 'gravityforms' ), "<strong>$min</strong>" );
		} else if ( is_numeric( $max ) ) {
			$message = sprintf( __( 'Please enter a value less than or equal to %s.', 'gravityforms' ), "<strong>$max</strong>" );
		} else if ( $this->failed_validation ) {
			$message = __( 'Please enter a valid number', 'gravityforms' );
		}

		return $message;
	}
	
	
	
	/**
	 * get_field_input function.
	 * 
	 * @access public
	 * @param mixed $form
	 * @param string $value (default: '')
	 * @param mixed $entry (default: null)
	 * @return void
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin        = $is_entry_detail || $is_form_editor;

		$form_id  = $form['id'];
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$form_id  = ( $is_entry_detail || $is_form_editor ) && empty( $form_id ) ? rgget( 'id' ) : $form_id;

		$size          = $this->size;
		$disabled_text = $is_form_editor ? "disabled='disabled'" : '';
		$class_suffix  = $is_entry_detail ? '_admin' : '';
		$class         = $this->type . ' ' .$size . $class_suffix;

		$form_sub_label_placement  = rgar( $form, 'subLabelPlacement' );
		$field_sub_label_placement = $this->subLabelPlacement;
		$is_sub_label_above        = $field_sub_label_placement == 'above' || ( empty( $field_sub_label_placement ) && $form_sub_label_placement == 'above' );
		$sub_label_class_attribute = $field_sub_label_placement == 'hidden_label' ? "class='hidden_sub_label screen-reader-text'" : '';
		
		$min_value = '';
		$max_malue = '';
		
		if( is_array( $value) ){
			$min_value = rgget( $this->id . '_min', $value );
			$max_value = rgget( $this->id . '_max', $value );
		}
		
		$instruction = '';
		$read_only   = '';

		if ( ! $is_entry_detail && ! $is_form_editor ) {

			if ( $this->has_calculation() ) {

				// calculation-enabled fields should be read only
				$read_only = 'readonly="readonly"';

			} else {

				$message          = $this->get_range_message();
				$validation_class = $this->failed_validation ? 'validation_message' : '';

				if ( ! $this->failed_validation && ! empty( $message ) && empty( $this->errorMessage ) ) {
					$instruction = "<div class='instruction $validation_class'>" . $message . '</div>';
				}
			}
		} else if ( RG_CURRENT_VIEW == 'entry' ) {
			$value = GFCommon::format_number( $value, $this->numberFormat, rgar( $entry, 'currency' )  );
		}

		$step = ( isset( $this->rangeslider_step ) && '' != $this->rangeslider_step ) ? $this->rangeslider_step : 1;

		$is_html5        = RGFormsModel::is_html5_enabled();
		$html_input_type = ! $this->has_calculation() && ( $this->numberFormat != 'currency' && $this->numberFormat != 'decimal_comma' ) ? 'number' : 'text'; // chrome does not allow number fields to have commas, calculations and currency values display numbers with commas
		$step_attr       = "step='{$this->rangeslider_step}'";

		$min = ( isset( $this->rangeMin ) && '' != $this->rangeMin ) ? $this->rangeMin : 0;
		$max = ( isset( $this->rangeMax ) && '' != $this->rangeMax ) ? $this->rangeMax : 10;

		$min_attr = "min='{$min}'";
		$max_attr = "max='{$max}'";

		$logic_event = $this->get_conditional_logic_event( 'change' );

		$include_thousands_sep = apply_filters( 'gform_include_thousands_sep_pre_format_number', $html_input_type == 'text', $this );
		$value = GFCommon::format_number( $value, $this->numberFormat, rgar( $entry, 'currency' ), $include_thousands_sep );
		
		$placeholder_attribute = $this->get_field_placeholder_attribute();
		$required_attribute    = $this->isRequired ? 'aria-required="true"' : '';
		$invalid_attribute     = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		
		$tabindex = $this->get_tabindex();

		$data_value_visibility = isset( $this->rangeslider_value_visibility ) ? "data-value-visibility='{$this->rangeslider_value_visibility}'" : "data-value-visibility='hidden'";

		if ( 'currency' == $this->numberFormat ) {
			// get current gravity forms currency
			$code = ! get_option( 'rg_gforms_currency' ) ? 'USD' : get_option( 'rg_gforms_currency' );
			if ( false === class_exists( 'RGCurrency' ) ) {
				require_once( GFCommon::get_base_path() . '/currency.php' );
			}
			$currency = new RGCurrency( GFCommon::get_currency() );
			$currency = $currency->get_currency( $code );

			// encode for html currency attribute
			$currency = "data-currency='" . json_encode($currency) . "'";
		} else {
			$currency = '';
		}
		
		$input = "<div class='ginput_container $class'>";
		$input .= "<div id='rangeslider_$id' class=''></div>";
		$input .= "<div id='rangeslider_{$id}_display' class='rangeslider_display'></div>";
		
		$input .= "<input name='input_{$id}' id='{$field_id}' type='hidden' value='$value' value='$value' />";
		$input .= "<input name='input_{$id}_min' id='{$field_id}_min' type='hidden' 
			{$logic_event}
			data-min-relation='".esc_attr( $this->rangeslider_min_value_relation )."' 
			data-max-relation='".esc_attr( $this->rangeslider_max_value_relation )."' 
			data-value-format='".esc_attr( $this->numberFormat )."'
			value='$min_value' />";
		$input .= "<input name='input_{$id}_max' id='{$field_id}_max' type='hidden' 
			{$logic_event}
			data-min-relation='".esc_attr( $this->rangeslider_min_value_relation )."' 
			data-max-relation='".esc_attr( $this->rangeslider_max_value_relation )."' 
			data-value-format='".esc_attr( $this->numberFormat )."'
			value='$max_value' />";
		
		$input .= $instruction;
		$input .= "</div>";
		
		/*$input = sprintf( "<input name='input_%d' id='%s' type='{$html_input_type}' {$step_attr} {$min_attr} {$max_attr} {$data_value_visibility} value='%s' class='%s' data-min-relation='%s' data-max-relation='%s' data-value-format='%s' {$currency} {$tabindex} {$logic_event} {$read_only} {$placeholder_attribute} %s %s %s/>%s", 
			$id, 
			$field_id, 
			esc_attr( $value ), 
			esc_attr( $class ), 
			esc_attr( $this->rangeslider_min_value_relation ), 
			esc_attr( $this->rangeslider_max_value_relation ), 
			esc_attr( $this->numberFormat ), 
			$disabled_text, 
			$required_attribute, 
			$invalid_attribute, 
			$instruction );*/
		$script = $this->generate_slider_script($field);
		if(is_admin()) $input .= "<script>$script</script>";
		
		return $input;
	}
	
	
	/**
	 * generate_slider_script function.
	 * 
	 * @access public
	 * @param mixed $field
	 * @return void
	 */
	public function generate_slider_script($field){
		$id       = intval( $field['id'] );
		$formid	  = $field['formId'];
		$field_id =  "input_".$formid."_$id" ;
		$size          = $field['size'];
		$class         = $field['type'] . ' ' .$size;
		
		$rs_var = 'rangeslider_'.$field['id'];
	   	$rs_var_display = $rs_var.'_display';
	   	$in_var = 'input_'.$formid.'_'.$field['id'];
	   	$in_var_min = 'input_'.$formid.'_'.$field['id'].'_min';
	   	$in_var_max = 'input_'.$formid.'_'.$field['id'].'_max';
		
		$sliderType 	= $field['sliderType'];
		$rangeMin 		= $field['rangeMin'];
		$rangeMax 		= $field['rangeMax'];
		$defaultMin	 	= $field['defaultMin'];
		$defaultMax 	= $field['defaultMax'];
		$rangeslider_step=$field['rangeslider_step'];
		$prefix 		= $field['prefix'];
		$thousand		= $field['thousand'];
		$betweenText 	= $field['betweenText'];
		$postfix 		= $field['postfix'];
		$decimals 		= $field['decimals'];
		$showTooltip 	= $field['showTooltip'];
		$showTextDisplay= ($field['showTextDisplay'])?'true':'false';
		$required 		= $field['required'];
		
	
		if($showTooltip == true) {
			$tooltip = ($sliderType == true)?"true":"[true, wNumb({
						decimals: $decimals,
						prefix: '$prefix',
						postfix: ' $postfix',
						thousand: '$thousand'
					})]";
		} else {
			$tooltip = 'false';	
		}
		
		$connect = ($sliderType == true)?'[true,false]':'true';
		$start = ($sliderType == true)?$defaultMin:"[$defaultMin, $defaultMax]";
		$inputValue = ($sliderType == true)?"minValue":"'' + minValue + ' $betweenText ' + maxValue;";
		$value = ($sliderType == true)?$prefix.$rangeMin.$postfix:$prefix.$rangeMin.$postfix.' '.$betweenText.' '. $prefix.$rangeMax.$postfix;
		
		
		$html_input_type = 'hidden'; 
	
	
		
		
		
		
		
		$script = "
		function hii_range_slider_init_$field_id(){
			var $rs_var = document.getElementById('$rs_var'),
				in_var_min = document.getElementById('$in_var_min'),
				in_var_max = document.getElementById('$in_var_max'),
				inputNumber = document.getElementById('$in_var'),
				showTextDisplay = $showTextDisplay,
				displayNumber = document.getElementById('$rs_var_display');
				noUiSlider.create($rs_var, {
					start: $start,
					connect: $connect,
					step: $rangeslider_step,
					tooltips: $tooltip,
					range: {
		'min': [   0, 5000 ],
		'25%': [  100000, 10000 ],
		'50%': [  300000, 50000 ],
		'75%': [  1000000, 1000000 ],    
		'max': [ 25000000 ]
},
					format: wNumb({
						decimals: $decimals,
						prefix: '$prefix',
						postfix: ' $postfix',
						thousand: '$thousand'
					})
					
				});
				var minValue = $defaultMin,
					maxValue = $defaultMax;
				$rs_var.noUiSlider.on('update', function( values, handle ) {
	
					var value = values[handle];
				
					if ( handle ) {
						maxValue = value;
					} else {
						minValue = value;
					}
					inputNumber.value = $inputValue;
					in_var_min.value = minValue;
					in_var_max.value = maxValue;
					if($showTextDisplay == true)displayNumber.innerHTML = $inputValue;
				});
		} 
		document.onreadystatechange = hii_range_slider_init_$field_id();";
	
		if($required == true)
		{
			$script .= "inputNumber.setAttribute('aria-required', 'true');";	
		}
		
		return $script; 
	}
	
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		$include_thousands_sep = apply_filters( 'gform_include_thousands_sep_pre_format_number', true, $this );
		
		return GFCommon::format_number( $value, $this->numberFormat, rgar( $entry, 'currency' ), $include_thousands_sep );
	}


	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		$include_thousands_sep = apply_filters( 'gform_include_thousands_sep_pre_format_number', $use_text, $this );
		
		return GFCommon::format_number( $value, $this->numberFormat, $currency, $include_thousands_sep );
	}

	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {
		$include_thousands_sep = apply_filters( 'gform_include_thousands_sep_pre_format_number', $modifier != 'value', $this );
		$formatted_value       = GFCommon::format_number( $value, $this->numberFormat, rgar( $entry, 'currency' ), $include_thousands_sep );
		
		return url_encode ? urlencode( $formatted_value ) : $formatted_value;
	}
	
	
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {

		$value = GFCommon::maybe_add_leading_zero( $value );

		$lead  = empty( $lead ) ? RGFormsModel::get_lead( $lead_id ) : $lead;
		$value = $this->has_calculation() ? GFCommon::round_number( GFCommon::calculate( $this, $form, $lead ), $this->calculationRounding ) : GFCommon::clean_number( $value, $this->numberFormat );
		//return the value as a string when it is zero and a calc so that the "==" comparison done when checking if the field has changed isn't treated as false
		if ( $this->has_calculation() && $value == 0 ) {
			$value = '0';
		}
		
		$value_safe = $this->sanitize_entry_value( $value, $form['id'] );
		
		return $value_safe;
	}
	
	public function sanitize_settings() {
		parent::sanitize_settings();
		$this->enableCalculation = (bool) $this->enableCalculation;

		if ( $this->numberFormat == 'currency' ) {
			require_once( GFCommon::get_base_path() . '/currency.php' );
			$currency = new RGCurrency( GFCommon::get_currency() );
			$this->rangeMin    = $currency->to_number( $this->rangeMin );
			$this->rangeMax    = $currency->to_number( $this->rangeMax );
		} elseif ( $this->numberFormat == 'decimal_comma' ) {
			$this->rangeMin = GFCommon::clean_number( $this->rangeMin, 'decimal_comma' );
			$this->rangeMax = GFCommon::clean_number( $this->rangeMax, 'decimal_comma' );
		} elseif ( $this->numberFormat == 'decimal_dot' ) {
			$this->rangeMin = GFCommon::clean_number( $this->rangeMin, 'decimal_dot' );
			$this->rangeMin = GFCommon::clean_number( $this->rangeMin, 'decimal_dot' );
		}
	}

	public function clean_number( $value ) {

		if ( $this->numberFormat == 'currency' ) {
			return GFCommon::to_number( $value );
		} else {
			return GFCommon::clean_number( $value, $this->numberFormat );
		}
	}

	
}

GF_Fields::register( new GFRangeslider() );
	
?>