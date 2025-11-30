<?php
/*
Plugin Name: FFF Danse
Description: CPT "Danse" med metadata + YouTube-import baseret på markerede felter.
Version: 1.0.0
Author: Jan Mikael + ChatGPT
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FFF_Danse_Plugin {

    const CPT          = 'danse';
    const OPTION_API   = 'fff_danse_api_key';
    const OPTION_FIELDS = 'fff_danse_import_fields';

    public function __construct() {
        // Core
        add_action( 'init',                     [ $this, 'register_post_type' ] );
        add_action( 'init',                     [ $this, 'register_meta_fields' ] );

        // Admin UI
        add_action( 'add_meta_boxes',           [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_' . self::CPT,   [ $this, 'save_meta_boxes' ] );

        add_action( 'admin_menu',               [ $this, 'register_settings_page' ] );
        add_action( 'admin_init',               [ $this, 'register_settings' ] );

        // Assets
        add_action( 'admin_enqueue_scripts',    [ $this, 'enqueue_admin_assets' ] );

        // AJAX
        add_action( 'wp_ajax_fff_danse_fetch_youtube', [ $this, 'ajax_fetch_youtube' ] );

        // Frontend single layout
        add_filter( 'the_content',              [ $this, 'filter_single_content' ] );
    }

    /**
     * Liste over alle felter (nøgler + labels + gruppe)
     */
    public static function get_fields() {
        return [
            // AUDIO
            'audio_lydfil'        => [ 'label' => 'Lydfil',            'group' => 'audio' ],
            'audio_kommentar1'    => [ 'label' => 'Lydkommentar 1',    'group' => 'audio' ],
            'audio_kommentar2'    => [ 'label' => 'Lydkommentar 2',    'group' => 'audio' ],
            'audio_note'          => [ 'label' => 'Lydnote',           'group' => 'audio' ],
            'audio_dato'          => [ 'label' => 'Lyddato',           'group' => 'audio' ],

            // BESKRIVELSE / PDF
            'BES_pdffil'          => [ 'label' => 'PDF',                'group' => 'beskrivelse' ],
            'BES_opstil'          => [ 'label' => 'PDF-opstilling',     'group' => 'beskrivelse' ],
            'BES_forklar'         => [ 'label' => 'PDF-forklaring',     'group' => 'beskrivelse' ],
            'BES_dato'            => [ 'label' => 'PDF-dato',           'group' => 'beskrivelse' ],

            // DANSE-INFO
            'danse_navn'          => [ 'label' => 'Dansenavn',          'group' => 'danseinfo' ],
            'danse_egn'           => [ 'label' => 'Egn',                'group' => 'danseinfo' ],
            'danse_ex_navn'       => [ 'label' => 'Alternativt navn',   'group' => 'danseinfo' ],
            'danse_ex_egn'        => [ 'label' => 'Alternativ egn',     'group' => 'danseinfo' ],
            'danse_topo'          => [ 'label' => 'Topografi',          'group' => 'danseinfo' ],
            'danse_hefte'         => [ 'label' => 'Hæfte',              'group' => 'danseinfo' ],
            'danse_side'          => [ 'label' => 'Side',               'group' => 'danseinfo' ],
            'danse_opstilling'    => [ 'label' => 'Danseopstilling',    'group' => 'danseinfo' ],
            'danse_trin'          => [ 'label' => 'Trinbeskrivelse',    'group' => 'danseinfo' ],
            'danse_musik'         => [ 'label' => 'Musikinfo',          'group' => 'danseinfo' ],
            'danse_takt'          => [ 'label' => 'Takt',               'group' => 'danseinfo' ],
            'danse_figur'         => [ 'label' => 'Figurbeskrivelse',   'group' => 'danseinfo' ],
            'danse_niveau'        => [ 'label' => 'Niveau',             'group' => 'danseinfo' ],
            'danse_dato'          => [ 'label' => 'Dansedato',          'group' => 'danseinfo' ],

            // HISTORIE (LYD)
            'historie_fil'        => [ 'label' => 'Historiefil',        'group' => 'historie_audio' ],
            'historie_kommentar1' => [ 'label' => 'Historiekommentar 1','group' => 'historie_audio' ],
            'historie_kommentar2' => [ 'label' => 'Historiekommentar 2','group' => 'historie_audio' ],
            'historie_note'       => [ 'label' => 'Historienote',       'group' => 'historie_audio' ],
            'historie_dato'       => [ 'label' => 'Historiedato',       'group' => 'historie_audio' ],

            // HISTORIE (TEKST)
            'historie_txt_text'   => [ 'label' => 'Historietekst',      'group' => 'historie_text' ],
            'historie_txt_note'   => [ 'label' => 'Tekstnote',          'group' => 'historie_text' ],
            'historie_txt_dato'   => [ 'label' => 'Tekstdato',          'group' => 'historie_text' ],

            // KOMMENTAR
            'kommentar_komm'      => [ 'label' => 'Kommentar',          'group' => 'kommentar' ],

            // LIGNENDE DANSE
            'ligner_ens'          => [ 'label' => 'Lignende danse',     'group' => 'lignende' ],

            // NODE
            'node_nodefil'        => [ 'label' => 'Nodefil',            'group' => 'node' ],
            'node_kommentar1'     => [ 'label' => 'Nodekommentar 1',    'group' => 'node' ],
            'node_kommentar2'     => [ 'label' => 'Nodekommentar 2',    'group' => 'node' ],
            'node_note'           => [ 'label' => 'Nodenote',           'group' => 'node' ],
            'node_dato'           => [ 'label' => 'Nodedato',           'group' => 'node' ],

            // NOTER
            'noter_niveau'        => [ 'label' => 'Noteniveau',         'group' => 'noter' ],
            'noter_instruk'       => [ 'label' => 'Instruktion',        'group' => 'noter' ],
            'noter_video'         => [ 'label' => 'Notevideo',          'group' => 'noter' ],
            'noter_andre'         => [ 'label' => 'Andre noter',        'group' => 'noter' ],
            'noter_dato'          => [ 'label' => 'Notedato',           'group' => 'noter' ],

            // TRIN / FIGUR VIDEO
            'trin_figur_video_trin'      => [ 'label' => 'Videotrin',            'group' => 'trin_figur' ],
            'trin_figur_video_figur'     => [ 'label' => 'Videfigur',            'group' => 'trin_figur' ],
            'trin_figur_video_videofil'  => [ 'label' => 'Trin/figur-video',     'group' => 'trin_figur' ],
            'trin_figur_video_kommentar1'=> [ 'label' => 'Trin/figur-komm. 1',   'group' => 'trin_figur' ],
            'trin_figur_video_kommentar2'=> [ 'label' => 'Trin/figur-komm. 2',   'group' => 'trin_figur' ],
            'trin_figur_video_dato'      => [ 'label' => 'Trin/figur-dato',      'group' => 'trin_figur' ],

            // VIDEO (INTRO / HOVED)
            'video_videofil'             => [ 'label' => 'Video',                'group' => 'video' ],
            'video_kommentar1'           => [ 'label' => 'Videokommentar 1',     'group' => 'video' ],
            'video_kommentar2'           => [ 'label' => 'Videokommentar 2',     'group' => 'video' ],
            'video_note'                 => [ 'label' => 'Videonote',            'group' => 'video' ],
            'video_dato'                 => [ 'label' => 'Videodato',            'group' => 'video' ],

            // VIDEO – alternativ version
            'video_ååååmmdd_videofil'    => [ 'label' => 'Alternativ video',     'group' => 'video_alt' ],
            'video_ååååmmdd_kommentar1'  => [ 'label' => 'Alt. kommentar 1',     'group' => 'video_alt' ],
            'video_ååååmmdd_kommentar2'  => [ 'label' => 'Alt. kommentar 2',     'group' => 'video_alt' ],
            'video_ååååmmdd_note'        => [ 'label' => 'Alt. note',            'group' => 'video_alt' ],
            'video_ååååmmdd_dato'        => [ 'label' => 'Alt. dato',            'group' => 'video_alt' ],
            
            // VIDEO – intro + sekvenser
            'video_intro'  => [ 'label' => 'Intro',  'group' => 'video_groups' ],
            'video_se1'    => [ 'label' => 'SE1',    'group' => 'video_groups' ],
            'video_se2'    => [ 'label' => 'SE2',    'group' => 'video_groups' ],
            'video_se3'    => [ 'label' => 'SE3',    'group' => 'video_groups' ],
            'video_lær1'   => [ 'label' => 'LÆR1',   'group' => 'video_groups' ],
            'video_lær2'   => [ 'label' => 'LÆR2',   'group' => 'video_groups' ],
            'video_lær3'   => [ 'label' => 'LÆR3',   'group' => 'video_groups' ],
            'video_dans1'  => [ 'label' => 'DANS1',  'group' => 'video_groups' ],
            'video_dans2'  => [ 'label' => 'DANS2',  'group' => 'video_groups' ],
            'video_dans3'  => [ 'label' => 'DANS3',  'group' => 'video_groups' ],
        ];
    }

    // CPT "Danse"
    public function register_post_type() {
        register_post_type( self::CPT, [
            'labels' => [
                'name'          => 'Danse',
                'singular_name' => 'Dans',
                'add_new'       => 'Tilføj dans',
                'add_new_item'  => 'Tilføj ny dans',
                'edit_item'     => 'Rediger dans',
                'new_item'      => 'Ny dans',
                'view_item'     => 'Vis dans',
                'search_items'  => 'Søg danse',
            ],
            'public'       => true,
            'has_archive'  => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-tickets-alt',
            'supports'     => [ 'title', 'editor', 'thumbnail' ],
        ] );
    }

    // Registrer meta-felter til REST mv.
    public function register_meta_fields() {
        foreach ( self::get_fields() as $key => $field ) {
            register_post_meta( self::CPT, $key, [
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            ] );
        }
    }

    // Metabox på "Danse"
    public function add_meta_boxes() {
        add_meta_box(
            'fff_danse_meta',
            'FFF Danse – metadata & YouTube',
            [ $this, 'render_meta_box' ],
            self::CPT,
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'fff_danse_meta_save', 'fff_danse_meta_nonce' );
        $fields  = self::get_fields();
        $allowed = get_option( self::OPTION_FIELDS, [] );
        if ( ! is_array( $allowed ) ) {
            $allowed = [];
        }

        // YouTube import-panel
        $api_key = get_option( self::OPTION_API, '' );
        ?>
        <div style="padding:10px 0;border-bottom:1px solid #ddd;margin-bottom:10px;">
            <p><strong>YouTube-import</strong></p>
            <?php if ( empty( $api_key ) ) : ?>
                <p style="color:#b00;">
                    ⚠️ Du har ikke angivet en YouTube API-nøgle endnu.
                    Gå til <em>Indstillinger → FFF Danse</em> for at sætte den.
                </p>
            <?php else : ?>
                <p>
                    <label for="fff_danse_youtube_url"><strong>YouTube URL eller video-ID:</strong></label><br/>
                    <input type="text" id="fff_danse_youtube_url" style="width:80%;" />
                    <button type="button" class="button" id="fff_danse_fetch_btn">
                        Hent fra YouTube
                    </button>
                </p>
                <p class="description">
                    Pluginet henter snippet (titel + beskrivelse) og parser linjer på formen
                    <code>felt_navn: værdi</code>. Det vil kun overskrive felter, der er markeret
                    som “Hente fra YouTube” under <em>Indstillinger → FFF Danse</em>.
                </p>
                <div id="fff_danse_fetch_result" style="margin-top:5px;"></div>
            <?php endif; ?>
        </div>
        <?php

        // Simpel grouping
        $groups = [];
        foreach ( $fields as $key => $field ) {
            $group_key = $field['group'];
            if ( ! isset( $groups[ $group_key ] ) ) {
                $groups[ $group_key ] = [];
            }
            $groups[ $group_key ][ $key ] = $field;
        }

        $group_labels = [
            'danseinfo'      => 'Danseinfo',
            'video'          => 'Video',
            'video_alt'      => 'Alternativ video',
            'trin_figur'     => 'Trin & figur-video',
            'audio'          => 'Lyd',
            'beskrivelse'    => 'Beskrivelse / PDF',
            'historie_audio' => 'Historie (lyd)',
            'historie_text'  => 'Historie (tekst)',
            'node'           => 'Noder',
            'noter'          => 'Noter',
            'lignende'       => 'Lignende danse',
            'kommentar'      => 'Kommentar',
        ];

        foreach ( $groups as $group_key => $group_fields ) {
            $title = isset( $group_labels[ $group_key ] ) ? $group_labels[ $group_key ] : ucfirst( $group_key );
            echo '<h3 style="margin-top:15px;">' . esc_html( $title ) . '</h3>';
            echo '<table class="form-table"><tbody>';
           foreach ( $group_fields as $key => $field ) {

    // Only show fields that are checked in Settings
    if ( ! in_array( $key, $allowed, true ) ) {
        continue;
    }

    $val = get_post_meta( $post->ID, $key, true );

    echo '<tr>';
    echo '<th scope="row"><label for="' . esc_attr( $key ) . '">' 
         . esc_html( $field['label'] ) . '</label></th>';

    echo '<td>';
    echo '<textarea name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" rows="2" style="width:100%;">'
         . esc_textarea( $val ) . '</textarea>';

    echo '<p class="description">✔ Dette felt er aktiveret i “Hente fra YouTube”.</p>';

    echo '</td>';
    echo '</tr>';
}            echo '</tbody></table>';
        }
    }

    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['fff_danse_meta_nonce'] ) || ! wp_verify_nonce( $_POST['fff_danse_meta_nonce'], 'fff_danse_meta_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = self::get_fields();
        foreach ( $fields as $key => $field ) {
            if ( isset( $_POST[ $key ] ) ) {
                $value = sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) );
                update_post_meta( $post_id, $key, $value );
            } else {
                // Hvis du hellere vil bevare gamle værdier ved tom POST, så kommentér næste linje ud
                delete_post_meta( $post_id, $key );
            }
        }
    }

    // Settings-side
    public function register_settings_page() {
        add_options_page(
            'FFF Danse',
            'FFF Danse',
            'manage_options',
            'fff-danse',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'fff_danse_settings', self::OPTION_API );
        register_setting( 'fff_danse_settings', self::OPTION_FIELDS );

        add_settings_section(
            'fff_danse_section_api',
            'YouTube API & import',
            function() {
                echo '<p>Angiv din YouTube Data API-nøgle og vælg hvilke felter, der må overskrives ved import.</p>';
            },
            'fff-danse'
        );

        add_settings_field(
            'fff_danse_api_key',
            'YouTube API-nøgle',
            [ $this, 'render_field_api_key' ],
            'fff-danse',
            'fff_danse_section_api'
        );

        add_settings_field(
            'fff_danse_import_fields',
            'Hente fra YouTube – felter',
            [ $this, 'render_field_import_fields' ],
            'fff-danse',
            'fff_danse_section_api'
        );
    }

    public function render_field_api_key() {
        $val = get_option( self::OPTION_API, '' );
        echo '<input type="text" name="' . esc_attr( self::OPTION_API ) . '" value="' . esc_attr( $val ) . '" style="width:400px;" />';
        echo '<p class="description">Bruges til at hente video-data fra YouTube Data API v3.</p>';
    }

    public function render_field_import_fields() {
        $fields  = self::get_fields();
        $allowed = get_option( self::OPTION_FIELDS, [] );
        if ( ! is_array( $allowed ) ) {
            $allowed = [];
        }

        echo '<p class="description">Sæt ✔ ved de felter, der må hentes/overskrives fra YouTube-beskrivelsen.</p>';
        echo '<div style="max-height:300px; overflow:auto; border:1px solid #ccc; padding:8px;">';
        foreach ( $fields as $key => $field ) {
            $checked = in_array( $key, $allowed, true ) ? 'checked' : '';
            echo '<label style="display:block; margin-bottom:4px;">';
            echo '<input type="checkbox" name="' . esc_attr( self::OPTION_FIELDS ) . '[]" value="' . esc_attr( $key ) . '" ' . $checked . ' /> ';
            echo '<code>' . esc_html( $key ) . '</code> – ' . esc_html( $field['label'] );
            echo '</label>';
        }
        echo '</div>';
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>FFF Danse – indstillinger</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'fff_danse_settings' );
                do_settings_sections( 'fff-danse' );
                submit_button();
                ?>
            </form>

            <h2>Sådan hænger det sammen</h2>
            <ol>
                <li>Opret eller redigér en <strong>Dans</strong> (CPT: Danse).</li>
                <li>Brug metaboxen “FFF Danse – metadata & YouTube” til at indtaste eller importere data.</li>
                <li>Ved YouTube-import:
                    <ul>
                        <li>Indsæt video-URL eller ID.</li>
                        <li>Pluginet henter <code>snippet.title</code> + <code>snippet.description</code>.</li>
                        <li>Parser linjer på formen <code>felt_navn: værdi</code>.</li>
                        <li>Kun felter markeret herunder som “Hente fra YouTube” bliver overskrevet.</li>
                    </ul>
                </li>
            </ol>
        </div>
        <?php
    }

    // Admin JS til AJAX-knap
    public function enqueue_admin_assets( $hook ) {
        global $post;
        if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
            if ( isset( $post ) && $post->post_type === self::CPT ) {
                wp_enqueue_script(
                    'fff-danse-admin',
                    plugin_dir_url( __FILE__ ) . 'js/fff-danse-admin.js',
                    [ 'jquery' ],
                    '1.0.0',
                    true
                );
                wp_localize_script(
                    'fff-danse-admin',
                    'FFF_DANSE',
                    [
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'nonce'    => wp_create_nonce( 'fff_danse_fetch' ),
                    ]
                );
            }
        }
    }

    // AJAX: hent YouTube-data
    public function ajax_fetch_youtube() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Ingen adgang.' ], 403 );
        }
        check_ajax_referer( 'fff_danse_fetch', 'nonce' );

        $post_id   = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        $video_raw = isset( $_POST['video'] ) ? trim( wp_unslash( $_POST['video'] ) ) : '';

        if ( ! $post_id || empty( $video_raw ) ) {
            wp_send_json_error( [ 'message' => 'Mangler post ID eller video-ID/URL.' ], 400 );
        }

        $api_key = get_option( self::OPTION_API, '' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'YouTube API-nøgle ikke sat.' ], 400 );
        }

        $video_id = $this->extract_video_id( $video_raw );
        if ( ! $video_id ) {
            wp_send_json_error( [ 'message' => 'Kunne ikke udlede video-ID fra input.' ], 400 );
        }

        $url = add_query_arg(
            [
                'part' => 'snippet',
                'id'   => $video_id,
                'key'  => $api_key,
            ],
            'https://www.googleapis.com/youtube/v3/videos'
        );

        $response = wp_remote_get( $url, [ 'timeout' => 15 ] );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => 'Fejl ved kald til YouTube: ' . $response->get_error_message() ], 500 );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code !== 200 || empty( $data['items'][0]['snippet'] ) ) {
            wp_send_json_error( [ 'message' => 'Uventet svar fra YouTube.', 'debug' => $data ], 500 );
        }

        $snippet     = $data['items'][0]['snippet'];
        $title       = isset( $snippet['title'] ) ? $snippet['title'] : '';
        $description = isset( $snippet['description'] ) ? $snippet['description'] : '';

        // Opdater felter
        $updated = $this->apply_import_to_post( $post_id, $video_id, $title, $description );

        wp_send_json_success( [
            'message' => 'YouTube-data hentet. Opdaterede felter: ' . implode( ', ', $updated ),
            'updated' => $updated,
        ] );
    }

    // Udtræk video-ID fra YouTube URL eller rå ID
    private function extract_video_id( $input ) {
        $input = trim( $input );

        // Hvis det ligner et rent ID (11 tegn, bogstaver/tal/_/-)
        if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $input ) ) {
            return $input;
        }

        // Ellers forsøg at trække v= fra URL
        if ( filter_var( $input, FILTER_VALIDATE_URL ) ) {
            $parts = wp_parse_url( $input );
            if ( ! empty( $parts['query'] ) ) {
                parse_str( $parts['query'], $query );
                if ( ! empty( $query['v'] ) ) {
                    return $query['v'];
                }
            }
            // Korte youtu.be/ID links
            if ( ! empty( $parts['host'] ) && $parts['host'] === 'youtu.be' && ! empty( $parts['path'] ) ) {
                return ltrim( $parts['path'], '/' );
            }
        }

        return '';
    }

    // Anvend import på post
    private function apply_import_to_post( $post_id, $video_id, $title, $description ) {
        $fields  = self::get_fields();
        $allowed = get_option( self::OPTION_FIELDS, [] );
        if ( ! is_array( $allowed ) ) {
            $allowed = [];
        }

        $updated = [];

        // Hvis titel skal hentes, bruger vi f.eks. danse_navn, hvis markeret
        if ( in_array( 'danse_navn', $allowed, true ) && ! empty( $title ) ) {
            update_post_meta( $post_id, 'danse_navn', wp_strip_all_tags( $title ) );
            $updated[] = 'danse_navn';
        }

        // Hvis video_videofil er markeret, gemmer vi video-ID
        if ( in_array( 'video_videofil', $allowed, true ) && ! empty( $video_id ) ) {
            update_post_meta( $post_id, 'video_videofil', $video_id );
            $updated[] = 'video_videofil';
        }

        // Parse description til "felt_navn: værdi"
        $lines = preg_split( '/\R/', (string) $description );
        foreach ( $lines as $line ) {
            if ( preg_match( '/^\s*([A-Za-z0-9_æøåÆØÅ]+)\s*:\s*(.+)$/u', $line, $m ) ) {
                $key   = trim( $m[1] );
                $value = trim( $m[2] );

                $key_lower = strtolower( $key );

                // Vi forventer at nøglerne matcher dine feltnavne, fx "danse_egn", "danse_takt" osv.
                if ( isset( $fields[ $key_lower ] ) && in_array( $key_lower, $allowed, true ) ) {
                    update_post_meta( $post_id, $key_lower, $value );
                    $updated[] = $key_lower;
                }
            }
        }

        // Fjern dubletter i updated
        $updated = array_values( array_unique( $updated ) );

        return $updated;
    }

    /**
     * Frontend: layout for single "Danse"
     * (simple, men giver et samlet billede – kan styles videre i temaet)
     */
    public function filter_single_content( $content ) {
        if ( ! is_singular( self::CPT ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $post_id = get_the_ID();
        $fields  = self::get_fields();

        // Hent alle meta
        $meta = [];
        foreach ( $fields as $key => $field ) {
            $meta[ $key ] = get_post_meta( $post_id, $key, true );
        }

        // Byg video-embed hvis video_videofil er sat
        $video_html = '';
        if ( ! empty( $meta['video_videofil'] ) ) {
            $vid        = esc_attr( $meta['video_videofil'] );
            $video_html = '<div class="fff-danse-video" style="margin-bottom:20px;">
                <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;">
                    <iframe src="https://www.youtube.com/embed/' . $vid . '" frameborder="0"
                        allowfullscreen
                        style="position:absolute;top:0;left:0;width:100%;height:100%;"></iframe>
                </div>
            </div>';
        }

        // Sektioner – meget enkel struktur
        ob_start();
        ?>
        <div class="fff-danse-single">
            <?php echo $video_html; ?>

            <div class="fff-danse-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <section>
                    <h2>Danseinfo</h2>
                    <?php $this->render_meta_list( $meta, [
                        'danse_navn',
                        'danse_egn',
                        'danse_ex_navn',
                        'danse_ex_egn',
                        'danse_topo',
                        'danse_hefte',
                        'danse_side',
                        'danse_opstilling',
                        'danse_trin',
                        'danse_musik',
                        'danse_takt',
                        'danse_figur',
                        'danse_niveau',
                        'danse_dato',
                    ], $fields ); ?>
                </section>

                <section>
                    <h2>Video & trin/figurer</h2>
                    <?php $this->render_meta_list( $meta, [
                        'video_kommentar1',
                        'video_kommentar2',
                        'video_note',
                        'video_dato',
                        'trin_figur_video_trin',
                        'trin_figur_video_figur',
                        'trin_figur_video_videofil',
                        'trin_figur_video_kommentar1',
                        'trin_figur_video_kommentar2',
                        'trin_figur_video_dato',
                    ], $fields ); ?>
                </section>

                <section>
                    <h2>Musik & noder</h2>
                    <?php $this->render_meta_list( $meta, [
                        'audio_lydfil',
                        'audio_kommentar1',
                        'audio_kommentar2',
                        'audio_note',
                        'audio_dato',
                        'node_nodefil',
                        'node_kommentar1',
                        'node_kommentar2',
                        'node_note',
                        'node_dato',
                    ], $fields ); ?>
                </section>

                <section>
                    <h2>Historier</h2>
                    <?php $this->render_meta_list( $meta, [
                        'historie_fil',
                        'historie_kommentar1',
                        'historie_kommentar2',
                        'historie_note',
                        'historie_dato',
                        'historie_txt_text',
                        'historie_txt_note',
                        'historie_txt_dato',
                    ], $fields ); ?>
                </section>

                <section>
                    <h2>Noter & kommentarer</h2>
                    <?php $this->render_meta_list( $meta, [
                        'noter_niveau',
                        'noter_instruk',
                        'noter_video',
                        'noter_andre',
                        'noter_dato',
                        'kommentar_komm',
                    ], $fields ); ?>
                </section>

                <section>
                    <h2>Lignende danse</h2>
                    <?php $this->render_meta_list( $meta, [
                        'ligner_ens',
                        'video_ååååmmdd_videofil',
                        'video_ååååmmdd_kommentar1',
                        'video_ååååmmdd_kommentar2',
                        'video_ååååmmdd_note',
                        'video_ååååmmdd_dato',
                    ], $fields ); ?>
                </section>
            </div>

            <div class="fff-danse-original-content" style="margin-top:30px;">
                <?php echo $content; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_meta_list( $meta, $keys, $fields ) {
        echo '<dl class="fff-danse-meta">';
        foreach ( $keys as $key ) {
            if ( empty( $meta[ $key ] ) ) {
                continue;
            }
            $label = isset( $fields[ $key ]['label'] ) ? $fields[ $key ]['label'] : $key;
            echo '<dt style="font-weight:bold;margin-top:8px;">' . esc_html( $label ) . '</dt>';
            echo '<dd style="margin:0 0 4px 0;">' . nl2br( esc_html( $meta[ $key ] ) ) . '</dd>';
        }
        echo '</dl>';
    }

}

new FFF_Danse_Plugin();

/**
 * Admin JS-fil (inline fallback, hvis du ikke vil lave separat .js-fil)
 *
 * Copypaste dette i en fil:
 *   wp-content/plugins/fff-danse/js/fff-danse-admin.js
 *
 * Indhold:
 *
 * (function($){
 *   $(function(){
 *     var $btn = $('#fff_danse_fetch_btn');
 *     if(!$btn.length) return;
 *
 *     $btn.on('click', function(e){
 *       e.preventDefault();
 *       var video = $('#fff_danse_youtube_url').val();
 *       var postId = $('#post_ID').val();
 *       var $result = $('#fff_danse_fetch_result');
 *
 *       if(!video){
 *         alert('Indtast en YouTube URL eller video-ID først.');
 *         return;
 *       }
 *
 *       $result.text('Henter data fra YouTube...');
 *
 *       $.post(FFF_DANSE.ajax_url, {
 *         action: 'fff_danse_fetch_youtube',
 *         nonce: FFF_DANSE.nonce,
 *         post_id: postId,
 *         video: video
 *       }).done(function(res){
 *         if(res.success){
 *           $result.css('color', '#0073aa').text(res.data.message || 'OK');
 *         } else {
 *           var msg = (res.data && res.data.message) ? res.data.message : 'Ukendt fejl.';
 *           $result.css('color', '#b32d2e').text('Fejl: ' + msg);
 *         }
 *       }).fail(function(){
 *         $result.css('color', '#b32d2e').text('Fejl ved AJAX-kald.');
 *       });
 *     });
 *   });
 * })(jQuery);
 */