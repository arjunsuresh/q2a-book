<?php
        
/*              
        Plugin Name: Book
        Plugin URI: https://github.com/NoahY/q2a-book
        Plugin Update Check URI: https://raw.github.com/NoahY/q2a-book/master/qa-plugin.php
        Plugin Description: Makes boook from top questions and answers
        Plugin Version: 0.8
        Plugin Date: 2012-03-05
        Plugin Author: NoahY
        Plugin Author URI:                              
        Plugin License: GPLv2                           
        Plugin Minimum Question2Answer Version: 1.5
*/                      
                        
                        
        if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
                        header('Location: ../../');
                        exit;   
        }               

        qa_register_plugin_module('module', 'qa-book-admin.php', 'qa_book_admin', 'Book Export');
        
        //qa_register_plugin_layer('qa-book-layer.php', 'Book Layer');

        qa_register_plugin_overrides('qa-book-overrides.php');

		qa_register_plugin_module('widget', 'qa-book-widget.php', 'qa_book_widget', 'Book Widget');
		
		qa_register_plugin_phrases('qa-book-lang-*.php', 'book');

		require 'util-book.php';


		function qa_book_plugin_createBook($return=false) {

			$book = qa_opt('book_plugin_template');
			
			// static replacements
			
			$book = str_replace('[css]',qa_opt('book_plugin_css'),$book);
			$book = str_replace('[front]',qa_opt('book_plugin_template_front'),$book);
			$book = str_replace('[back]',qa_opt('book_plugin_template_back'),$book);			
			$shuffle = true;
			$shuffle = false;
			$iscats = qa_opt('book_plugin_cats');

			// categories

			if($iscats) {
			    $cats = qa_db_read_all_assoc(
					qa_db_query_sub(
						'SELECT * FROM ^categories'.(qa_opt('book_plugin_catex')?' WHERE categoryid NOT IN ('.qa_opt('book_plugin_catex').')':'')
					)
				);	
				$navcats = array();
				foreach($cats as $cat)
					$navcats[$cat['categoryid']] = $cat;
			}
			else
			    $cats = array(false);

			// intro
			
			$intro = qa_lang('book/intro');
			$ack = qa_lang('book/ack');
			
			$intro = str_replace('[sort_questions]',qa_lang('book/'.(qa_opt('book_plugin_sort_q') == 0?'sort_upvotes':'sort_date')),$intro);
			$intro = str_replace('[sort_categories]',$iscats?qa_lang('book/sort_categories'):'',$intro);
			$intro = str_replace('[restrict_questions]',qa_opt('book_plugin_req_qv')?qa_lang_sub('book/restrict_q_x_votes',qa_opt('book_plugin_req_qv_no')):qa_lang('book/all_questions'),$intro);
			
			$rq = array();
			
			if(qa_opt('book_plugin_req_sel'))
				$rq[] = qa_lang('book/restrict_selected');
			if(qa_opt('book_plugin_req_abest'))
				$rq[] = qa_lang('book/restrict_best_a');
			if(qa_opt('book_plugin_req_av'))
				$rq[] = qa_lang_sub('book/restrict_a_x_votes',qa_opt('book_plugin_req_av_no'));

			if(empty($rq))
				$intro = str_replace('[restrict_answers]','',$intro);
			else {
				$rqs = qa_lang('book/restrict_answers_clause_'.count($rq));
				foreach($rq as $i => $v) 
					$rqs = str_replace('('.($i+1).')',$v,$rqs);
				$intro = str_replace('[restrict_answers]',$rqs,$intro);
			}
			
			$book = str_replace('[intro]',$intro,$book);
			$book = str_replace('[ack]',$ack,$book);

			    
			$tocout = '';
			$qout = '';
			
				$ccount=0;
			foreach($cats as $cat) {
				$qcount=0;
				$incsql = '';
				$anssql = 'ans.type = \'A\' ';
				$sortsql = '';
				
				$toc = '';
				$qhtml = '';
				
				if(qa_opt('book_plugin_sort_q') == 0)
					$sortsql='ORDER BY qs.netvotes DESC, qs.created ASC';
				else
					$sortsql='ORDER BY qs.created ASC';

				if(qa_opt('book_plugin_req_sel'))
					$incsql .= ' AND qs.selchildid=ans.postid';
					
				if(qa_opt('book_plugin_req_abest'))
					$sortsql.=',  ans.netvotes DESC'; // get all, limit later with break
					
				if(qa_opt('book_plugin_req_qv'))
					$incsql .= ' AND qs.netvotes >= '.(int)qa_opt('book_plugin_req_qv_no');

				if(qa_opt('book_plugin_req_av'))
					$anssql .= ' AND ans.netvotes >= '.(int)qa_opt('book_plugin_req_av_no');
				$filter = true;
				$skipanswers = true;
			//	$skipanswers = false;
				//$filter = false;
				if($filter){
					$incsql .= ' and (qs.title  like \'GATE%\' || qs.title like \'TIFR%\')';
				//	$incsql .= ' and (qs.title not  like \'GATE%\' and qs.title not like \'TIFR%\')';
				}
				if($skipanswers){
					$anssql .= ' AND  ans.postid < 0 '; 
				}
				$sortsql.=',  ans.netvotes DESC'; // get all, limit later with break
					
				$selectspec="SELECT qs.postid AS postid, BINARY qs.title AS title, BINARY qs.content AS content, qs.format AS format, qs.netvotes AS netvotes, qs.tags as tags, qs.selchildid as selected, ans.postid as apostid, BINARY ans.content AS acontent, ans.format AS aformat, ans.userid AS auserid, ans.netvotes AS anetvotes FROM ^posts  qs left outer join ^posts  ans on qs.postid=ans.parentid and  ".$anssql." where qs.type='Q' ".($iscats?" AND qs.categoryid=".$cat['categoryid']." ":"AND qs.categoryid NOT IN  (62,63)") . $incsql." ".$sortsql;
				
				$qs = qa_db_read_all_assoc(
					qa_db_query_sub(
						$selectspec
					)
				);	
				
				if(empty($qs)) // no questions in this category
					continue;
				
				$ccount++;
				$q2 = array();
				foreach($qs as $q) { // group by questions
					$q2['q'.$q['postid']][] = $q;
				}
				
				//usort($q2,array($this,  "mysort"));
				if(!$shuffle)
					usort($q2, "mysort");
				else
				{
					//shuffle($q2);
				}
				$oldmint='';	
				foreach($q2 as $qs) {
				if(skiptags($qs[0]))
					continue;	
				usort($qs, "mysortanswers");
					// toc entry
					$mint = mintag($qs[0]);
					if($mint !== $oldmint){
						if($mint !== '' && !$shuffle)
						{
						$toc.=str_replace('[qlink]','<a href="#question'.$qs[0]['postid'].'">'.$mint.'</a>',qa_opt('book_plugin_template_toc'));
						}
						else {
					//		$toc.=str_replace('[qlink]','<a href="#question'.$qs[0]['postid'].'">'.$qs[0]['title'].'</a>',qa_opt('book_plugin_template_toc'));
						}
						$oldmint = $mint;
					}

					// answer html
					
					$as = '';
					$nv = false;
					$noanswer = true;
					foreach($qs as $idx => $q) {
						if(qa_opt('book_plugin_req_abest') && qa_opt('book_plugin_req_abest_max') && $idx >= qa_opt('book_plugin_req_abest_max'))
							break;
						if($nv !== false && qa_opt('book_plugin_req_abest') && $nv != $q['anetvotes']) // best answers only
							break;
					/*arjun*/
						if($idx && ($q['anetvotes'] == 0))	
							break;
						$acontent = '';
						if(!empty($q['acontent'])) {
							$viewer=qa_load_viewer($q['acontent'], $q['aformat']);
							$acontent = $viewer->get_html($q['acontent'], $q['aformat'], array());
						$noanswer = false;
						}
						$a = str_replace('[answer]',$acontent,qa_opt('book_plugin_template_answer'));
						if($q['selected'] == $q['apostid'] && $q['apostid'] !== NULL){
							$a = str_replace('[best]', 'bestanswer', $a);
							$a = str_replace('[bestanswer]', 'bestanswercontent', $a);
							$a = str_replace('[beforebest]', '<div class="tick"> <button disabled class="qa-a-select-button"><span class="fa fa-check"></span></button></div><div class="best-text">Selected Answer</div>', $a);
							$a = str_replace('[afterbest]', '', $a);
						}
						else{
							$a = str_replace('[best]', '', $a);
							$a = str_replace('[bestanswer]', '', $a);
							$a = str_replace('[beforebest]', '', $a);
							$a = str_replace('[afterbest]', '', $a);
						}
						if($q['auserid'] !== NULL)	
							$a = str_replace('[answerer]',qa_get_user_name($q['auserid']),$a);
						else {
							$a = str_replace('[answerer]','',$a);
						}
						$nv = $q['anetvotes'];
						if($q['anetvotes'] !== NULL)	
							$a = str_replace('[votes]',$nv,$a);
						else {
							$a = str_replace('[votes]','',$a);
						}
						$res = qa_db_query_sub("select points from ^userpoints where userid=#", $q['auserid']);
						$points = qa_db_read_one_value($res, true);
						if($q['auserid'] !== NULL)	
							$a = str_replace('[upoints]',trim($points),$a);
						else {
							$a = str_replace('[upoints]','',$a);
						}
						$as .= $a;
						
					}
					// question html
					$qcontent = '';
					if(!empty($q['content'])) {
					$qcount++;
						$viewer=qa_load_viewer($q['content'], $q['format']);
						$qcontent = $viewer->get_html($q['content'], $q['format'], array());
					}
					$tagshtml='';
					$tags = qa_tagstring_to_tags($q['tags']);
					$mint=mintag($q);
					foreach ($tags as $tag)
					{
				
						$tagshtml.="<li class=\"qa-q-view-tag-item\"> <a href=\"http://gateoverflow.in/tag/".$tag."\"    class=\"qa-tag-link\">".$tag." </a></li>";
					}
					if($mint !== '')
						$mint.=": ";
					$number="<div class=\"number\">".$ccount.".".$qcount."</div>";	
					$titleright="<div class=\"title-right\"><a href=\"http://gateoverflow.in/".$q['postid']."\">gateoverflow.in/".$q['postid']."</a></div>";
					$oneq = str_replace('[question-title]',$number.$mint.$q['title'].$titleright,qa_opt('book_plugin_template_question'));
					$oneq = str_replace('[qanchor]','question'.$q['postid'],$oneq);
					$oneq = str_replace('[qurl]',qa_html(qa_q_request($q['postid'],$q['title'])),$oneq);
					$oneq = str_replace('[question]',$qcontent,$oneq);
					 // output with answers 
					 $oneq = str_replace('[tags]', $tagshtml, $oneq);
					if($skipanswers || $noanswer)
					$qhtml .= str_replace('[answers]','',$oneq);
					else
					$qhtml .= str_replace('[answers]',$as,$oneq);
				}
				if($iscats) {
					$tocout .= '<li><a href="#cat'.$cat['categoryid'].'" onclick="toggle(\'cat'.$cat['categoryid'].'Details\')" class="toc-cat">'.$cat['title'].'</a><span id="cat'.$cat['categoryid'].'Details"> <ul class="toc-ul">'.$toc.'</ul></li>';

					// todo fix category link
					$catnumber="<div class=\"number\">$ccount</div>";
					$catout = str_replace('[cat-url]',qa_path_html('questions/'.qa_category_path_request($navcats, $cat['categoryid'])),qa_opt('book_plugin_template_category'));
					$catout = str_replace('[cat-anchor]','cat'.$cat['categoryid'],$catout);
					$catout = str_replace('[cat-title]',$catnumber.$cat['title'],$catout);
					$catout = str_replace('[questions]',$qhtml,$catout);
					$qout .= $catout;
				}
				else {
					if(!$shuffle)
						$tocout .= '<ul class="toc-ul">'.$toc.'</ul>';
					$catout = str_replace('[questions]',$qhtml,qa_opt('book_plugin_template_questions'));
					$qout .= $catout;
				}
			}	
			if($iscats)
				$tocout = '<ul class="toc-ul">'.$tocout.'</ul>';
				
			// add toc and questions
			
			$book = str_replace('[toc]',$tocout,$book);
			$book = str_replace('[categories]',$qout,$book);
			
			// misc subs
			
			$book = str_replace('[site-title]',qa_opt('site_title'),$book);
			$book = str_replace('[site-url]',qa_opt('site_url'),$book);
			$book = str_replace('[date]',date('M j, Y'),$book);
			
			qa_opt('book_plugin_refresh_last',time());
			
			error_log('Q2A Book Created on '.date('M j, Y \a\t H\:i\:s'));
			
			if($return)
				return $book;
			
			file_put_contents(qa_opt('book_plugin_loc'),$book);
			
			if(qa_opt('book_plugin_pdf'))
				qa_book_plugin_create_pdf();


			return 'Book Created';
		    
		    //return 'Error creating '.qa_opt('book_plugin_loc').'; check the error log.';
		}
		
		function qa_book_plugin_create_pdf($return=false) {
				
			include 'wkhtmltopdf.php';

			//echo $html;

			$pdf = new WKPDF();

			$pdf->render_q2a();
			
			if($return)
				$pdf->output(WKPDF::$PDF_DOWNLOAD,'book.pdf'); 
			else
				$pdf->output(WKPDF::$PDF_SAVEFILE,qa_opt('book_plugin_loc_pdf')); 

			error_log('Q2A PDF Book Created on '.date('M j, Y \a\t H\:i\:s'));
		}

		function qa_get_user_name($uid) {

			$handles = qa_userids_to_handles(array($uid));
			$handle = $handles[$uid];

			if(QA_FINAL_EXTERNAL_USERS) {
				$user_info = get_userdata($uid);
				if ($user_info->display_name)
					$name = $user_info->display_name;
			}
			else {
				$name = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT content AS name FROM ^userprofile '.
						'WHERE userid=# AND title=$',
						$uid, 'name'
					),
					true
				);
			}
			if(!@$name)
				$name = $handle;

			return strlen($handle) ? ('<A HREF="'.qa_path_absolute('user/'.$handle).
				'" CLASS="qa-user-link">'.qa_html($name).'</A>') : 'Anonymous';
		}

                        
/*                              
        Omit PHP closing tag to help avoid accidental output
*/                              
                          

