<?php

/**
 * Plugin Name: LitProtocol WP:: Lit-Gated
 * Plugin URI: https://litprotocol.com
 * Description: Token-gate your post/page using <a href="https://litprotocol.com">Lit-Protocol</a>
 * Version: 0.0.1
 * Author: WebSaam.com
 * Author URI:  https://websaam.com
 * License: GPLv3
 */
 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


// =================================================================================
// +                                Define Constants                               +
// =================================================================================

// -- define libraries
define("WEB_URL", plugin_dir_url(__FILE__));
define("DIR_URL", plugin_dir_path(__FILE__));
define('LIT_ADMIN_HOOK', 'toplevel_page_lit-gated');
define('LIT_ACC_MODAL_CSS', 'https://cdn.jsdelivr.net/npm/lit-access-control-conditions-modal-vanilla-js/dist/main.css');
define('LIT_ACC_MODAL_JS', 'https://cdn.jsdelivr.net/npm/lit-access-control-conditions-modal-vanilla-js/dist/index.js');
define('LIT_VERIFY_JS', 'https://jscdn.litgateway.com/index.web.js');
define('LIT_ADMIN_CSS', WEB_URL . 'wp-lit-gated-admin.css');
define('LIT_APP_CSS', WEB_URL . 'wp-lit-gated-app.css');
define('LIT_JWT_API', 'https://jwt-verification-service.lit-protocol.workers.dev');
define('LIT_JWT_TEST_TOKEN', "eyJhbGciOiJCTFMxMi0zODEiLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJMSVQiLCJzdWIiOiIweGRiZDM2MGYzMDA5N2ZiNmQ5MzhkY2M4YjdiNjI4NTRiMzYxNjBiNDUiLCJjaGFpbiI6InBvbHlnb24iLCJpYXQiOjE2NDIwOTU2ODcsImV4cCI6MTY0MjEzODg4NywiYmFzZVVybCI6Im15LWR5bmFtaWMtY29udGVudC1zZXJ2ZXIuY29tIiwicGF0aCI6Ii9jYnJ0MjdrOW5lZnh6endudHYweWgiLCJvcmdJZCI6IiIsInJvbGUiOiIiLCJleHRyYURhdGEiOiIifQ.qT9tHi1jOwQ4ha89Sn-WyvQK9GVjjQrPzRK20IskkmxkQJy_cLLGuCNFgRQiDcNiBgajZ83qITlJye1ZbciNrcJiM-uNs8LuEOfftxegOgj_WY-o17G3ZUtte1ehZoNT");

// -- define admin menu page
define('LIT_ICON', site_url() . '/wp-content/plugins/wp-lit-gated/assets/favicon-16x16.png');
define('LIT_MENU_NAME', 'Lit-Gated');
define('LIT_MENU_SLUG', 'lit-gated');
define('LIT_MENU_PAGE_CONTENT', DIR_URL . "/setup/menu-page.php");
define('LIT_MENU_GROUP', 'lit-settings');

include(DIR_URL . "/setup/Setup.php");


// ================================================================================
// +                        Hooking up all required scripts                       +
// ================================================================================
// --- Load scripts in the admin panel on our specific option page to use the access control conditions modal
function lit_enqueue_acc_modal_css($hook) {
    if( $hook != LIT_ADMIN_HOOK ) return;
    wp_enqueue_style('lit-modal-css', LIT_ACC_MODAL_CSS);
}
function lit_enqueue_acc_modal_js($hook) {
    if( $hook != LIT_ADMIN_HOOK ) return;
    wp_enqueue_script('lit-modal-js', LIT_ACC_MODAL_JS);
}
function lit_enqueue_admin_css($hook) {
    if( $hook != LIT_ADMIN_HOOK ) return;
    wp_enqueue_style( 'custom-css', LIT_ADMIN_CSS);
}
function lit_enqueue_verify_js($hook) {
    if( $hook != LIT_ADMIN_HOOK ) return;
    wp_enqueue_script( 'lit-verify-js', LIT_VERIFY_JS);
}

// --- Load script for front-end
function lit_enqueue_app_css($hook) {
    wp_enqueue_style( 'lit-app-css', LIT_APP_CSS);
}
add_action( 'wp_enqueue_scripts', 'lit_enqueue_app_css' );

// -- execute
add_action( 'admin_enqueue_scripts', 'lit_enqueue_acc_modal_css' );
add_action( 'admin_enqueue_scripts', 'lit_enqueue_acc_modal_js' );
add_action( 'admin_enqueue_scripts', 'lit_enqueue_admin_css' );
add_action( 'admin_enqueue_scripts', 'lit_enqueue_verify_js' );


// ================================================================================
// +                                     Helper                                   +
// ================================================================================

/**
 * Request Header Parts
 * @return { Object } 
 */
function lit_request(){
    $obj = new stdClass();
    $obj->protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
    $obj->url = "$obj->protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $obj->base_url = "$_SERVER[HTTP_HOST]";
    $obj->path = rtrim($_SERVER['REQUEST_URI'],"/");
    return $obj;
}

/**
 * Simple Log to the screen
 * @param { String } title
 * @param { String } slug
 * @return { void } 
 */
function console($title, $log){
    $debug = false;
    if( ! $debug ) return;

    echo '<div class="lit-debug">';
    echo '<br>============ '.$title.' ============<br>';
    echo '<pre><code>';
    print_r($log);
    echo '</code></pre>';
    echo '</div>';
}

/**
 * JS Style API Fetching
 * @param { String } $url
 * @param { Array } $data
 * @return { Object } response
 *  eg.------------------------
 *   $res = fetch(LIT_JWT_API, [
 *      "jwt" => LIT_JWT_TEST_TOKEN
 *  ]);
 *  var_dump($res);
 */
function fetch($url, $data){
    $ch = curl_init();

    $headers = [
        "User-Agent: Lit-Gated Wordpress Plugin",
        "Content-Type: application/json"
    ];

    $json_string = json_encode($data);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);

    return json_decode($data);
}

// =================================================================================
// +                                Start Capturing                                +
// =================================================================================
//
// scan through the whole page and store its content to a variable
// all HTML pages are essential a string
//
add_action('wp_head', function(){
    ob_start();
});
add_action('wp_footer', function ($callback){
    $content = ob_get_clean();

// =================================================================================
// +                              Completed Capturing                              +
// =================================================================================
    
    $settings = json_decode(base64_decode(get_option('lit-settings')));

    // ================================================================================
    // +                        No Acccess Control Pages Setup                        +
    // ================================================================================
    if($settings == null){
        echo $content;
        exit();
    }

    // =================================================================================
    // +                                   All Pages                                   +
    // =================================================================================
    
    // -- New 
    $locked_list = array_map(function($data){
        return $data->anchor;
    }, $settings);
    console("New List", $locked_list);

    // -- find current object
    $found_entry = null;
    for($i = 0; $i < count($settings); $i++){
        $data = $settings[$i];
        if($data->anchor == lit_request()->url){
            $found_entry = $data;
        }
    }
    console('New Found Match', $found_entry);


    // ==================================================================================
    // +                             Non-Lit-Gated Page                             +
    // ==================================================================================
    if( ! in_array(lit_request()->url, $locked_list)){
        console('***** Non-Lit-Gated Page *****', lit_request()->url);
        echo $content;
        exit();
    }

    // ==================================================================================
    // +                               Lit-Gated Page                               +
    // ==================================================================================
 
    // -- get data from database
    $access_controls = $found_entry->accs;
    $resource_id = '{"baseUrl":"'.lit_request()->base_url.'","path":"'.lit_request()->path.'","orgId":"","role":"","extraData":""}';
    
    // ==================================================================================
    // +                              BEFORE POST REQUEST                               +
    // ==================================================================================
    if(empty($_POST)){
        echo '
            <div class="lit-gated">
                <section>
                    <img src="https://litprotocol.com/lit-logo.png" alt="Lit Protocol" />
                    <h4>This page is Lit-Gated</h4>
                    <div id="lit-msg"></div>
                    <form action="'.htmlspecialchars(lit_request()->url).'" method="POST" id="lit-form">
                        <input type="hidden" id="jwt" name="jwt" value="">
                        <input type="submit" id="lit-submit" value="Unlock Page">
                    </form>
                </section>
            </div>
        ';
    }else{   
    // ==================================================================================
    // +                                AFTER POST REQUEST                               +
    // ==================================================================================

        $res = fetch(LIT_JWT_API, ["jwt" => $_POST["jwt"]]);

        // LIT Developers: change this to the baseUrl you are authenticating, path, and other params in the payload
        // so that they match the resourceId that you used when you saved the signing condition to the Lit Protocol
        if($res->verified == false || 
            $res->payload->baseUrl !== $_SERVER["HTTP_HOST"] ||
            $res->payload->orgId !== '' ||
            $res->payload->role !== '' ||
            $res->payload->extraData !== ''){
            echo "Not Authorized";
        }else{
            // LIT Developers: This is the success condition. Change this to whatever URL you want to redirect to if auth works properly
            echo $content;
        }
        
    }
    
    // ================================================================================
    // + WARNING! Following Javascript is rendered client-side, which means is public.+
    // ================================================================================
    echo '<script src="'.LIT_VERIFY_JS.'"></script>';
    echo '<script>
        LitJsSdk.litJsSdkLoadedInALIT();
        (async () => {
            const btnSubmit = document.getElementById("lit-submit");
            const form = document.getElementById("lit-form");
            
            console.log("---Mounted---");
            btnSubmit.classList.add("lit-active");
            const accessControlConditions = '.$access_controls.';
            const resourceId = '.$resource_id.';
            console.log("________");
            console.log(accessControlConditions);
            console.log(resourceId);
            const readable = await LitJsSdk.humanizeAccessControlConditions({accessControlConditions});
            document.getElementById("lit-msg").innerHTML = readable;


            btnSubmit.addEventListener("click", async (e) => {
                e.preventDefault();
                const litNodeClient = new LitJsSdk.LitNodeClient();
                await litNodeClient.connect();
    
                const chain = "ethereum";
                const authSig = await LitJsSdk.checkAndSignAuthMessage({chain: chain});
                const jwt = await litNodeClient.getSignedToken({ accessControlConditions, chain, authSig, resourceId });
                
                console.log("🤌 JWT:", jwt);                
                document.getElementById("jwt").setAttribute("value", jwt);

                form.submit();
            });

        })();
    </script>';
    exit();

});