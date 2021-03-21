<?php
/*
Plugin Name: Utility for Relyx
Plugin URI:
Description: Shortcode, hook and so on.
＊【ショートコード】rlx_postlist：カスタム投稿を含む投稿リスト（横並び）
＊【ショートコード】rlx_homeurl：サイトのURLを返す
＊【ショートコード】rlx_year_diff：期間算出（年）
＊【アクションフック】rlx_header_note：H1テキスト挿入
＊【フィルターフック】（制作中）H1テキスト挿入時H1→P
Author: Relyx
Version: 0.5
Author URI: https://tech.relyx.jp/
*/

// リスト・テキスト挿入ON時のみCSSを吐き出す
function rlx_utl_link_css(){
    // echo '<link rel="stylesheet" type="text/css" href="', plugins_url('/style.css', __FILE__), '">';
    wp_enqueue_style( 'rlx_utl_css', plugins_url('style.css', __FILE__ ));
}
add_action( 'wp_enqueue_scripts', 'rlx_utl_link_css', 1 );
// add_action('wp_head', 'rlx_utl_link_css', 1);

/*Site URL*/
function sc_homeurl( $arg, $content = '' ) {
	return esc_url( home_url() ).$content;
}
add_shortcode( 'rlx_homeurl', 'sc_homeurl' );

/*年数表示*/
/*differece of the 2 years*/
function sc_year_diff( $arg ){
    $baseyear = $arg[0];
    $diffyear = date('Y');
    if(count($arg) > 1 ){
        $diffyear = $arg[1];
    }
    return ($diffyear - $baseyear);
}
add_shortcode('rlx_year_diff', 'sc_year_diff');

/*
    function name : rlx_header_note
    augument :
        addclass ： 追加クラス名
    description :
        ヘッダーにカスタムフィールドで追加したH1テキストを表示する
        子テーマでヘッダー付近のアクションフックに登録してください
        H1テキストのカスタムフィールド指定はオプション画面にて
        （開発中のため現在はheader_h1_text固定）
*/
function rlx_header_note( $arg ){
    $addclass = ' class="rlx_header_note_box ';
    if( is_array( $arg ) ){
        $addclass .= $arg[0] . '"';
    }elseif( !empty( $arg ) ){
        $addclass .= $arg . '"';
    }

    // $note_text = $post->post_name . '：postオブジェクトのpost_name';
    // $note_text .= get_the_title() . '：get_the_title';
    $note_text = get_post_meta(get_the_ID(),'header_h1_text')[0];
            // $note_tag = '';
            // $note_class = '';
            // $note_text = get_post_meta(get_the_ID(),'header_h1_text')[0];
            // $ptype = get_post_type();
            // if($ptype == 'page'){
            //     $note_tag = 'h1';
            //     if(empty($note_text)){
            //         $note_class = ' class="note-sage"';
            //         $note_text = get_bloginfo(). single_post_title('の', false);
            //     }
            // }elseif(is_single()){
            //     // アーカイブが1件のみの場合ここに入ってしまうっぽい
            //     $note_tag = 'p';
            //     if(empty($note_text)){
            //         $note_class = ' class="note-sage"';
            //         $note_text = single_post_title('', false);
            //     }
            // }else{
            //     $note_tag = 'h1';
            //     $type_name = '';
            //     if(empty($note_text)){
            //         if(is_category()){
            //             $type_name = __('Category', 'lightning');
            //         }elseif(is_tag()){
            //             $type_name = __('Tag', 'lightning');
            //         }elseif (is_search()){
            //             $type_name = __('Search', 'lightning');
            //         }elseif (is_attachment()){
            //             $type_name = __('Attachment', 'lightning');
            //         }else{
            //             $type_name = __('Archive', 'lightning');
            //         }

            //         $note_class = ' class="note-sage"';
            //         $note_text = get_bloginfo(). 'の' . $type_name;
            //     }
            // }
        echo '<div', $addclass, '><div class="rlx_header_note"><h1>', $note_text,'</h1></div></div>';
        // echo '<div class="rlx_header_note"><',
        //     $note_tag, $note_class, '>', $note_text, '</', $note_tag, '></div>';

        return true;
}




/*shortcode:postlist*/
/*
    function name : sc_get_postlist
    augument :
        ptype    ： 【必須】記事の投稿タイプ
        num      ： 【必須】記事リストの取得数
        cat      ： 記事のカテゴリーまたはターム（カスタム投稿記事のカテゴリーのスラッグ）
        txtype   ： 記事のタクソノミー（カスタムカテゴリーのスラッグ）タイプ
        pdate    ： 日付表示（[true]/false）
        content  ： 記事本文表示（all/ex/[none]）
        thumb    ： サムネイル表示（true/[false]）
        random   ： ランダム表示（true/[false]）
        addclass ： 追加クラス名
    description :
        【投稿リスト表示】
        投稿タイプ ptype の投稿を num 件取得して日付降順またはランダムで表示する
        postの場合カテゴリー cat で絞り込み可能
        カスタム投稿の場合、カスタムカテゴリー（タクソノミー） txtype および カテゴリー（ターム） cat で絞り込み可能
        日付表示 ON/OFF可能（規定値：ON）
        記事本文表示 全文/抜粋/非表示可能（規定値：非表示）
        画像サムネイル表示 ON/OFF可能（規定値：OFF）
        ランダム表示 ON/OFF可能（規定値：OFF）
        リストのdivに個別にクラス追加可能
*/
function sc_get_postlist($arg) {
    //引数を設定値としてセット
    if( array_keys($arg)[0] === 0){     //キー指定なし
        $ptype = $arg[0];               //記事の投稿タイプ【必須】
        $num = $arg[1];                 //記事リストの取得数【必須】
        $cat = false;                      //表示する記事のカテゴリー
        $txtype = false;                   //記事のタクソノミータイプ
        $pdate = true;                  //日付表示（[true]/false）
        $content = 'none';              //記事本文表示（all/ex/[none]）
        $thumb = false;                 //サムネイル表示（true/[false]）
        $random = false;                //ランダム表示（true/[false]）
        $addclass = '';                 //追加クラス名
        if( count($arg) > 2 ):          //オプション項目が指定されていればセット
            $cat = $arg[2];
        endif;
        if( count($arg) > 3 ):
            $txtype = $arg[3];
        endif;
        if( count($arg) > 4 ):
            if($arg[4] === "false"):
                $pdate = false;
            endif;
        endif;
        if( count($arg) > 5 ):
            $content = $arg[5];
        endif;
        if( count($arg) > 6 ):
            if($arg[6] === "true"):
                $thumb = true;
            endif;
        endif;
        if( count($arg) > 7 ):
            if($arg[7] === "true"):
                $random = true;
            endif;
        endif;
        if( count($arg) > 8 ):
            $addclass = $arg[8];
        endif;
    }else{                              //引数キー指定あり
        extract(shortcode_atts(array(
            'ptype' => 'post',          //記事の投稿タイプ【必須】
            'num' => '',                //記事リストの取得数【必須】
            'cat' => false,                //表示する記事のカテゴリー
            'txtype' => false,             //記事のタクソノミータイプ
            'pdate' => true,            //日付表示（[true]/false）
            'content' => 'none',        //記事本文表示（all/ex/[none]）
            'thumb' => false,           //サムネイル表示（true/[false]）
            'random' => false,          //ランダム表示（true/[false]）
            'addclass' => ''            //追加クラス名
        ), $arg));
        //true/false振り直し
        if( $pdate === "true" ){
            $pdate = true;
        }else{
            $pdate = false;
        }
        if( $thumb === "true" ){
            $thumb = true;
        }else{
            $thumb = false;
        }
        if( $random === "true" ){
            $random = true;
        }else{
            $random = false;
        }
    }
    global $post;
    $oldpost = $post;                   //変数postの初期値待避
    if( empty( $cat ) ):                //catが空指定の場合falseに設定
        $cat = false;
    endif;
    if( empty( $txtype ) ):             //txtypeが空指定の場合falseに設定
        $txtype = false;
    endif;

    $sort = 'date';                     //表示順（規定：日付順）
    if( $random ):                      //表示順ランダムに設定
        $sort = 'rand';
    endif;
    $postarg = array(                   //get_postsの引数設定
        'post_type' => $ptype,
        'numberposts' => $num,
        'order' => 'DESC',
        'orderby' => $sort,
    );
//    $debugs= '';
    if( $ptype == 'post' ){
        if($cat):
            $postarg += array( 'category' => $cat );
        endif;
    }else{
        //catのみが指定されている場合
        if( $cat && ($txtype === false) ):
            $exterm = term_exists( $cat );
            //指定の投稿タイプのタクソノミー内にcatがある場合のみタクソノミーをセット
            if ( $exterm !== 0 && $exterm !== null ){
                //タクソノミーを検索
                $txnms = get_object_taxonomies( $ptype, 'names');
                foreach ( $txnms as $txnm ):
                    $terms = get_terms( $txnm ) ;
                    if ( ! empty( $terms ) && !is_wp_error( $terms ) ):
                        foreach( $terms as $term ):
                            if( $term->slug == $cat ):
                                $txtype = $txnm;
                            endif;
                        endforeach;
                    endif;
                endforeach;
            }else{
                $cat = false;
            }
        endif;
        if( $cat && $txtype ):
            $postarg += array(
                'tax_query' => array(
                    array(
                        'taxonomy'=> $txtype,
                        'field' => 'slug',
                        'terms' => $cat
                    )
                )
            );
        endif;
    }
    $gposts = get_posts( $postarg );

    $ret_html='<div class="rlx_postlist '. $addclass . '">';

    if( !empty($gposts) ){              //取得した投稿があったら処理

        $ret_html.='<ul>';              //投稿リスト開始

        foreach($gposts as $post):
            setup_postdata($post);      //コンテンツなどすべての要素にアクセス設定

            // v0.3表示ポスト数に合わせてクラス指定（最大5まで対応）
            if( $num > 5 ):
                $num = 5;
            endif;

            $ret_html.='<li class="rlx_postlist_post rlx_postlist_postnum' . $num . '">';
            if( $thumb ):               //サムネイル表示あり
                $ret_html.='<div class="rlx_postlist_thumb">';
                if(has_post_thumbnail()){
                    $ret_html.=get_the_post_thumbnail($post->ID,'medium');
                }else{
                    $ret_html.='<div class="rlx_postlist_noimg"><i class="fa fa-camera" aria-hidden="true"></i></div>';
                }
                $ret_html.='</div>';
            endif;
            //日付表示あり
            if( $pdate ):
                $ret_html.='<div class="rlx_postlist_date">'.get_the_date().'</div>';
            endif;
            //タイトル
            $ret_html.='<div class="rlx_postlist_title"><a href="'.get_permalink().'">'.the_title("","",false).'</a></div>';
            //本文
            if( $content === "all" ){   //コンテンツ全文
                $ret_html.='<div class="rlx_postlist_content">'. do_shortcode(get_the_content()) .'</div>';
                // $ret_html.='<div class="rlx_postlist_content">'. do_shortcode(the_content()) .'</div>';
                // $ret_html.='<div class="rlx_postlist_content">'.get_the_content().'</div>';
            }elseif ( $content === "ex") { //抜粋
                $ret_html.='<div class="rlx_postlist_content">'.get_the_excerpt().'</div>';
            }

            $ret_html.='</li>';

        endforeach;

        if(count($gposts)<$num):        //取得投稿数が指定数より少なかった場合空liを出力
            for($i = count($gposts);$i < $num; $i++){
                $ret_html.='<li class="rlx_postlist_postnum' . $num . '"></li>';
            }
        endif;

        $ret_html.='</ul>';             //リスト終了
    }else{                               //ポストなし

        $ret_html.='<div class="' . $addclass . '_no_post"></div>';

    }

    $ret_html.='</div>';
    //投稿データ処理リセット
    $post = $oldpost;
    wp_reset_postdata();

    return $ret_html;

}
add_shortcode('rlx_postlist', 'sc_get_postlist');



?>