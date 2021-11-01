<?php
/**
 * Plugin Name: 	Enphase Gateway
 * Description:   Display information from your enphase solar installation
 * Requires at least: 	5.8
 * Requires PHP: 	7.3
 * Version: 0.2
 * Author: 		Laura Dean
 * License: 		MIT
 */

add_action('admin_menu', 'enphase_gateway_setup_menu');
add_action( 'admin_init', 'update_enphase_gateway_settings' );
add_shortcode('enphase', 'enphase');

function enphase_gateway_setup_menu(){
    add_menu_page( 'Enphase Gateway Page', 'Enphase Gateway', 'manage_options', 'enphase-gateway', 'enphase_gateway_init' );
}

if( !function_exists("update_enphase_gateway_settings") ) {
  function update_enphase_gateway_settings() {
    register_setting( 'enphase-gateway-settings', 'enphase_user_id' );
    register_setting( 'enphase-gateway-settings', 'enphase_api_key' );
    register_setting( 'enphase-gateway-settings', 'enphase_system_id' );
    register_setting( 'enphase-gateway-settings', 'enphase_days' );
  }
}

function enphase_gateway_init(){ ?>

    <h1>Enphase Gateway Settings</h1>

    <p>Please <a href="https://enlighten.enphaseenergy.com/app_user_auth/new?app_id=1409622100757">Authorize Plugin</a> before use, this will also generate your user ID.</p>
</p>
    <p>Add [enphase] to post or page to display usage graph.</p>

    <form method="post" action="options.php">
      <?php settings_fields( 'enphase-gateway-settings' ); ?>
      <?php do_settings_sections( 'enphase-gateway-settings' ); ?>
      <table class="form-table">
        <tr valign="top"><th scope="row">User ID</th>
        <td><input type="text" name="enphase_user_id" value="<?php echo esc_textarea(get_option( 'enphase_user_id' )); ?>"/>
        </td></tr>
        <tr valign="top"><th scope="row">API Key</th>
        <td><input type="text" name="enphase_api_key" value="<?php echo esc_textarea(get_option( 'enphase_api_key' )); ?>"/>
        </td></tr>
        <tr valign="top"><th scope="row">Site ID</th>
        <td><input type="text" name="enphase_system_id" value="<?php echo esc_textarea(get_option( 'enphase_system_id' )); ?>"/>
        </td></tr>
        <tr valign="top"><th scope="row">Show last # days</th>
        <td><input type="text" name="enphase_days" value="<?php echo esc_textarea(get_option( 'enphase_days' )); ?>"/>
        </td></tr>
      </table>
      <?php submit_button(); ?>
    </form>

<?php }

function enphase() {
  ob_start();
  show_enphase_graph();
  $content = ob_get_clean();
  return $content;
}

function show_enphase_graph() {

  console.log('start');

  add_option('enphase_data');
  $enphase_data = get_option('enphase_data');

  $days = get_option('enphase_days');
  $start_date = date('Y-m-d', strtotime('-' .$days. ' days'));
  $end_date = date('Y-m-d');
  $user_id = get_option('enphase_user_id');
  $key = get_option('enphase_api_key');
  $system_id = get_option('enphase_system_id');

  wp_enqueue_script('loader', 'https://www.gstatic.com/charts/loader.js', null, null);
  wp_enqueue_script('enphase-chart', plugin_dir_url(__FILE__) . 'enphase-chart.js', 'loader', null);

  if ($enphase_data
      && $enphase_data['start_date'] === $start_date
      && $enphase_data['end_date'] === $end_date
      && $enphase_data['api_key'] === $key
      && $enphase_data['user_id'] === $user_id
      && $enphase_data['system_id'] === $system_id) {
    $enphaseData = $enphase_data['chart_data'];
  }

  else {

    // DATES
    $begin = new DateTime($start_date);
    $end = new DateTime($end_date);

    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($begin, $interval, $end);

    foreach ($period as $dt) {
        $enphaseData[][0] = $dt->format('m-d');
    }

    // PRODUCTION
    $url = 'https://api.enphaseenergy.com/api/v2/systems/' .$system_id. '/energy_lifetime?key=' .$key. '&user_id=' .$user_id. '&start_date=' .$start_date;
    $result = wp_remote_get( $url );
    $body = json_decode($result['body']);

    foreach( $body->production as $index => $value ) {
      array_push($enphaseData[$index], $value);
    }

    // CONSUMPTION
    $url2 = 'https://api.enphaseenergy.com/api/v2/systems/' .$system_id. '/consumption_lifetime?key=' .$key. '&user_id=' .$user_id. '&start_date=' .$start_date;
    $result2 = wp_remote_get( $url2 );
    $body2 = json_decode($result2['body']);

    foreach( $body2->consumption as $index => $value ) {
      array_push($enphaseData[$index], $value);
    }

    // LABELS
    array_unshift($enphaseData, ['Date']);
    if (count($body->production)) {
      array_push($enphaseData[0], 'Produced');
    }
    if (count($body2->consumption)) {
      array_push($enphaseData[0], 'Consumed');
    }

    //$enphaseChartData = json_encode($enphaseData);
    $enphase_data = array(
      'user_id' => $user_id,
      'api_key' => $key,
      'system_id' => $system_id,
      'start_date' => $start_date,
      'gen_date' => $today,
      'chart_data' => $enphaseData
    );
    update_option('enphase_data', $enphase_data);

  }

  console.log($enphaseData);

  wp_localize_script( 'enphase-chart', 'enphaseChartData', $enphaseData );

  ?>

  <div id='enphase_chart' style="margin-bottom:20px;background-color:white;height:350px;width:100%;"></div>
  <a href="http://enphase.com/">
    <div style="display: inline-block; height: 30px; vertical-align: top;">Powered by</div>
    <img style="height: 33px;" src="<?php echo esc_url(plugin_dir_url( __FILE__ ) . 'images/Powered_By_Enphase_Logo_RGB.png'); ?>">
  </a>

  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <script type="text/javascript">
    //google.charts.load('current', {'packages':['bar']});

  </script>
  <?php
}
