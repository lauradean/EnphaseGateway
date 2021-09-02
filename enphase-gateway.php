<?php
/**
 * Plugin Name: 	Enphase Gateway
 * Description:   Display information from your enphase solar installation
 * Requires at least: 	5.8
 * Requires PHP: 	7.4
 * Version: 0.2
 * Author: 		Laura Dean
 * License: 		MIT
 */

add_action('admin_menu', 'enphase_gateway_setup_menu');
add_action( 'admin_init', 'update_enphase_gateway_settings' );
add_shortcode('enphase', 'show_enphase_graph');

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
        <td><input type="text" name="enphase_user_id" value="<?php echo get_option( 'enphase_user_id' ); ?>"/>
        </td></tr>
        <tr valign="top"><th scope="row">API Key</th>
        <td><input type="text" name="enphase_api_key" value="<?php echo get_option( 'enphase_api_key' ); ?>"/>
        </td></tr>
        <tr valign="top"><th scope="row">Site ID</th>
        <td><input type="text" name="enphase_system_id" value="<?php echo get_option( 'enphase_system_id' ); ?>"/>
        </td></tr>
        <tr valign="top"><th scope="row">Show last # days</th>
        <td><input type="text" name="enphase_days" value="<?php echo get_option( 'enphase_days' ); ?>"/>
        </td></tr>
      </table>
      <?php submit_button(); ?>
    </form>

<?php }


function show_enphase_graph() {

  add_option('enphase_data');
  $enphase_data = get_option('enphase_data');

  $days = get_option('enphase_days');
  $start_date = date('Y-m-d', strtotime('-' .$days. ' days'));
  $user_id = get_option('enphase_user_id');
  $key = get_option('enphase_api_key');
  $system_id = get_option('enphase_system_id');

  if ($enphase_data
      && $enphase_data['start_date'] === $start_date
      && $enphase_data['end_date'] === date('Y-m-d')
      && $enphase_data['api_key'] === $key
      && $enphase_data['user_id'] === $user_id
      && $enphase_data['system_id'] === $system_id) {
    $enphaseChartData = json_encode($enphase_data['chart_data']);
  }

  else {

    // PRODUCTION
    $url = 'https://api.enphaseenergy.com/api/v2/systems/' .$system_id. '/energy_lifetime?key=' .$key. '&user_id=' .$user_id. '&start_date=' .$start_date;
    $result = wp_remote_get( $url );
    $body = json_decode($result['body']);

    foreach( $body->production as $index => $value ) {
      $date = date('Y-m-d', strtotime($body->start_date. ' + '. $index .' days'));
      $enphaseData[] = array(0 => $date, 1 => $value);
    }

    // CONSUMPTION
    $url2 = 'https://api.enphaseenergy.com/api/v2/systems/' .$system_id. '/consumption_lifetime?key=' .$key. '&user_id=' .$user_id. '&start_date=' .$start_date;
    $result2 = wp_remote_get( $url2 );
    $body2 = json_decode($result2['body']);

    foreach( $body2->consumption as $index => $value ) {
      array_push($enphaseData[$index], $value);
    }

    array_unshift($enphaseData, ['Date', 'Produced (Wh)', 'Consumed (Wh)']);
    $enphaseChartData = json_encode($enphaseData);

    $enphase_data = array(
      'user_id' => $user_id,
      'api_key' => $key,
      'system_id' => $system_id,
      'start_date' => $start_date,
      'end_date' => date('Y-m-d'),
      'chart_data' => $enphaseData
    );
    update_option('enphase_data', $enphase_data);

  }

  ?>

  <a href="http://enphase.com/">
    <div style="display: inline-block; height: 30px; vertical-align: top;">Powered by</div>
    <img style="height: 33px;" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/Powered_By_Enphase_Logo_RGB.png'; ?>">
  </a>
  <div id='enphase_chart' style="padding:20px;background-color:white;"></div>

  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <script type="text/javascript">
    google.charts.load('current', {'packages':['bar']});
    google.charts.setOnLoadCallback(drawEnphaseChart);

    function drawEnphaseChart() {
      var data = google.visualization.arrayToDataTable(<?= $enphaseChartData ?>);

      var options = {
        chart: {
          title: 'Energy Production & Consumption',
          subtitle: 'Last <?= $days ?> days',
        },
        legend: {textStyle: {fontSize: 15}},
      };
      var chart = new google.charts.Bar(document.getElementById('enphase_chart'));

      chart.draw(data, google.charts.Bar.convertOptions(options));
    }
  </script>
  <?php
}
