// if ( $this->theme_json_exists() ) {
		// 	// typography => wp_get_global_settings( [ 'typography', 'fontSizes', 'default' ], 'theme' );
		// 	// color => wp_get_global_settings( [ 'color', 'palette' ], 'theme' );
		// 	// gradients => wp_get_global_settings( [ 'color', 'gradients' ], 'theme' );

		// 	// echo '<pre>';
		// 	// var_dump( wp_get_global_settings( [ 'color', 'palette' ], 'theme' ) );
		// 	// echo '</pre>';


		// } else {

		// }

		// $feature_value = current( (array) get_theme_support( $feature_key ) );

		// if ( ! is_array( $feature_value ) || empty( $feature_value ) ) {
		// 	continue;
		// }

		// // if ( $reduced ) {
		// 	$features[ $feature_key ] = [];

		// 	foreach ( $feature_value as $item ) {
		// 		if ( $this->has_required_feature_props( $feature_key, $item ) ) {
		// 			$features[ $feature_key ][] = wp_array_slice_assoc( $item, self::SUPPORTED_FEATURES[ $feature_key ] );
		// 		}
		// 	}

		// 	// var_dump(json_encode(wp_array_slice_assoc( $item, self::SUPPORTED_FEATURES[ $feature_key ] )));

		// 	exit;
		// // } else {
		// // 	$features[ $feature_key ] = $feature_value;
		// // }
