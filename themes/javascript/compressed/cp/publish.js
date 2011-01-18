/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

EE.publish=EE.publish||{};
EE.publish.category_editor=function(){var c=[],e=$("<div />"),b=$('<div id="cat_modal_container" />').appendTo(e),h={},q={},m=EE.BASE+"&C=admin_content&M=category_editor&group_id=",o,k,j;e.dialog({autoOpen:false,height:450,width:600,modal:true});$(".edit_categories_link").each(function(){var f=this.href.substr(this.href.lastIndexOf("=")+1);$(this).data("gid",f);c.push(f)});for(j=0;j<c.length;j++){h[c[j]]=$("#cat_group_container_"+[c[j]]);h[c[j]].data("gid",c[j]);q[c[j]]=$("#cat_group_container_"+
[c[j]]).find(".cat_action_buttons").remove()}o=function(f){h[f].text("loading...").load(m+f+"&timestamp="+ +new Date+" .pageContents table",function(){k.call(h[f],h[f].html(),false)})};k=function(f,p){var g=$(this),a=g.data("gid");f=$.trim(f);if(g.hasClass("edit_categories_link"))g=$("#cat_group_container_"+a);if(f[0]!=="<"&&p)return o(a);g.closest(".cat_group_container").find("#refresh_categories").show();var d=$(f),i;if(d.find("form").length){b.html(d);d=b.find("input[type=submit]");i=b.find("form");
var l=function(r){var t=r||$(this);r=t.serialize();t=t.attr("action");$.ajax({url:t,type:"POST",data:r,dataType:"html",beforeSend:function(){g.html(EE.lang.loading)},success:function(n){n=$.trim(n);e.dialog("close");if(n[0]=="<"){n=$(n).find(".pageContents table");n.find("form").length==0&&g.html(n);k.call(g,n,true)}else k.call(g,n,true)},error:function(n){e.dialog("close");k.call(g,n.error,true)}});return false};i.submit(l);var s={};s[d.remove().attr("value")]=function(){l(i)};e.dialog("open");e.dialog("option",
"buttons",s);e.one("dialogclose",function(){o(a)})}else q[a].clone().appendTo(g).show();return false};j=function(){$(this).hide();var f=$(this).data("gid"),p=".pageContents";if($(this).hasClass("edit_cat_order_trigger")||$(this).hasClass("edit_categories_link"))p+=" table";f||(f=$(this).closest(".cat_group_container").data("gid"));h[f].text(EE.lang.loading);$.ajax({url:this.href+"&timestamp="+ +new Date+p,success:function(g){var a="";g=$.trim(g);if(g[0]=="<"){g=$(g).find(p);a=$("<div />").append(g).html();
g.find("form").length==0&&h[f].html(a)}k.call(h[f],a,true)},error:function(g){g=eval("("+g.responseText+")");h[f].html(g.error);k.call(h[f],g.error,true)}});return false};$(".edit_categories_link").click(j);$(".cat_group_container a:not(.cats_done)").live("click",j);$(".cats_done").live("click",function(){var f=$(this).closest(".cat_group_container"),p=f.data("gid");$(".edit_categories_link").each(function(){$(this).data("gid")==p&&$(this).show()});f.text("loading...").load(EE.BASE+"&C=content_publish&M=category_actions&group_id="+
f.data("gid")+"&timestamp="+ +new Date,function(g){f.html($(g).html())});return false})};var selected_tab="";function get_selected_tab(){return selected_tab}
function tab_focus(c){$(".menu_"+c).parent().is(":visible")||$("a.delete_tab[href=#"+c+"]").trigger("click");$(".tab_menu li").removeClass("current");$(".menu_"+c).parent().addClass("current");$(".main_tab").hide();$("#"+c).fadeIn("fast");$(".main_tab").css("z-index","");$("#"+c).css("z-index","5");selected_tab=c;$(".main_tab").sortable("refreshPositions")}EE.tab_focus=tab_focus;
function setup_tabs(){var c="";$(".main_tab").sortable({connectWith:".main_tab",appendTo:"#holder",helper:"clone",forceHelperSize:true,handle:".handle",start:function(e,b){b.item.css("width",$(this).parent().css("width"))},stop:function(e,b){b.item.css("width","100%")}});$(".tab_menu li a").droppable({accept:".field_selector, .publish_field",tolerance:"pointer",forceHelperSize:true,deactivate:function(){clearTimeout(c);$(".tab_menu li").removeClass("highlight_tab")},drop:function(e,b){field_id=b.draggable.attr("id").substring(11);
tab_id=$(this).attr("title").substring(5);$("#hold_field_"+field_id).prependTo("#"+tab_id);$("#hold_field_"+field_id).hide().slideDown();tab_focus(tab_id);return false},over:function(){tab_id=$(this).attr("title").substring(5);$(this).parent().addClass("highlight_tab");c=setTimeout(function(){tab_focus(tab_id);return false},500)},out:function(){c!=""&&clearTimeout(c);$(this).parent().removeClass("highlight_tab")}});$("#holder .main_tab").droppable({accept:".field_selector",tolerance:"pointer",drop:function(e,
b){field_id=b.draggable.attr("id")=="hide_title"||b.draggable.attr("id")=="hide_url_title"?b.draggable.attr("id").substring(5):b.draggable.attr("id").substring(11);tab_id=$(this).attr("id");$("#hold_field_"+field_id).prependTo("#"+tab_id);$("#hold_field_"+field_id).hide().slideDown()}});$(".tab_menu li.content_tab a, #publish_tab_list a.menu_focus").unbind(".publish_tabs").bind("mousedown.publish_tabs",function(e){tab_id=$(this).attr("title").substring(5);tab_focus(tab_id);e.preventDefault()}).bind("click.publish_tabs",
function(){return false})}setup_tabs();Number.prototype.is_integer=String.prototype.is_integer=function(){var c=parseInt(this,10);if(isNaN(c))return false;return this==c&&this.toString()==c.toString()};EE.publish.get_percentage_width=function(c){if(c.attr("data-width")&&c.attr("data-width").slice(0,-1).is_integer())return parseInt(c.attr("data-width"),10);return Math.round(c.width()/c.parent().width()*10)*10};
EE.publish.save_layout=function(){var c=0,e={},b={},h=0,q=false,m=$("#tab_menu_tabs li.current").attr("id");$(".main_tab").show();$("#tab_menu_tabs a:not(.add_tab_link)").each(function(){if($(this).parent("li").attr("id")&&$(this).parent("li").attr("id").substring(0,5)=="menu_"){var f=$(this).parent("li").attr("id").substring(5),p=$(this).parent("li").attr("id").substring(5),g=$(this).parent("li").attr("title");h=0;visible=true;if($(this).parent("li").is(":visible")){lay_name=f;e[lay_name]={};e[lay_name]._tab_label=
g}else{q=true;visible=false}$("#"+p).find(".publish_field").each(function(){var a=$(this),d=this.id.replace(/hold_field_/,"");a=EE.publish.get_percentage_width(a);var i=$("#sub_hold_field_"+d+" .markItUp ul li:eq(2)");if(a>100)a=100;i=i.html()!=="undefined"&&i.css("display")!=="none"?true:false;a={visible:$(this).css("display")==="none"||visible===false?false:true,collapse:$("#sub_hold_field_"+d).css("display")==="none"?true:false,htmlbuttons:i,width:a+"%"};if(visible===true){a.index=h;e[lay_name][d]=
a;h+=1}else b[d]=a});visible===true&&c++}});if(q==true){var o,k,j=0;for(darn in e){k=darn;for(o in e[k])if(e[k][o].index>j)j=e[k][o].index;break}$.each(b,function(){this.index=++j});jQuery.extend(e[k],b)}EE.tab_focus(m.replace(/menu_/,""));if(c===0)$.ee_notice(EE.publish.lang.tab_count_zero,{type:"error"});else $("#layout_groups_holder input:checked").length===0?$.ee_notice(EE.publish.lang.no_member_groups,{type:"error"}):$.ajax({type:"POST",dataType:"json",url:EE.BASE+"&C=content_publish&M=save_layout",
data:"XID="+EE.XID+"&json_tab_layout="+JSON.stringify(e)+"&"+$("#layout_groups_holder input").serialize()+"&channel_id="+EE.publish.channel_id,success:function(f){if(f.messageType==="success")$.ee_notice(f.message,{type:"success"});else f.messageType==="failure"&&$.ee_notice(f.message,{type:"error"})}})};
EE.publish.remove_layout=function(){if($("#layout_groups_holder input:checked").length===0)return $.ee_notice(EE.publish.lang.no_member_groups,{type:"error"});$.ajax({type:"POST",url:EE.BASE+"&C=content_publish&M=save_layout",data:"XID="+EE.XID+"&json_tab_layout={}&"+$("#layout_groups_holder input").serialize()+"&channel_id="+EE.publish.channel_id+"&field_group="+EE.publish.field_group,success:function(){$.ee_notice(EE.publish.lang.layout_removed+' <a href="javascript:location=location">'+EE.publish.lang.refresh_layout+
"</a>",{duration:0,type:"success"});return true}});return false};EE.publish.change_preview_link=function(){$select=$("#layout_preview select");$link=$("#layout_group_preview");base=$link.attr("href").split("layout_preview")[0];$link.attr("href",base+"layout_preview="+$select.val());$.ajax({url:EE.BASE+"&C=content_publish&M=preview_layout",type:"POST",dataType:"json",data:{XID:EE.XID,member_group:$select.find("option:selected").text()}})};
EE.date_obj_time=function(){var c=new Date,e=c.getHours();c=c.getMinutes();var b="";if(c<10)c="0"+c;if(EE.date.format=="us"){b=e<12?" AM":" PM";if(e!=0)e=(e+11)%12+1}return" '"+e+":"+c+b+"'"}();file_manager_context="";
function disable_fields(c){var e=$(".main_tab input, .main_tab textarea, .main_tab select, #submit_button"),b=$("#submit_button"),h=$("#holder").find("a");if(c){e.attr("disabled",true);b.addClass("disabled_field");h.addClass("admin_mode");$("#holder div.markItUp, #holder p.spellcheck").each(function(){$(this).before('<div class="cover" style="position:absolute;width:100%;height:50px;z-index:9999;"></div>').css({})})}else{e.removeAttr("disabled");b.removeClass("disabled_field");h.removeClass("admin_mode");
$(".cover").remove()}}
function liveUrlTitle(){var c=EE.publish.default_entry_title,e=EE.publish.word_separator,b=document.getElementById("title").value||"",h=document.getElementById("url_title"),q=RegExp(e+"{2,}","g"),m=e!=="_"?/\_/g:/\-/g,o="";if(c!=="")if(b.substr(0,c.length)===c)b=b.substr(c.length);b=EE.publish.url_title_prefix+b;b=b.toLowerCase().replace(m,e);for(c=0;c<b.length;c++){m=b.charCodeAt(c);if(m>=32&&m<128)o+=b.charAt(c);else if(m in EE.publish.foreignChars)o+=EE.publish.foreignChars[m]}b=o;b=b.replace("/<(.*?)>/g",
"");b=b.replace(/\s+/g,e);b=b.replace(/\//g,e);b=b.replace(/[^a-z0-9\-\._]/g,"");b=b.replace(/\+/g,e);b=b.replace(q,e);b=b.replace(/^[-_]|[-_]$/g,"");b=b.replace(/\.+$/g,"");if(h)h.value=b.substring(0,75)}
$(document).ready(function(){function c(a){if(a){a=a.toString();a=a.replace(/\(\!\(([\s\S]*?)\)\!\)/g,function(d,i){var l=i.split("|!|");return altKey===true?l[1]!==undefined?l[1]:l[0]:l[1]===undefined?"":l[0]});return a=a.replace(/\[\!\[([\s\S]*?)\]\!\]/g,function(d,i){var l=i.split(":!:");if(p===true)return false;value=prompt(l[0],l[1]?l[1]:"");if(value===null)p=true;return value})}return""}function e(a,d){var i=$("input[name="+d+"]").closest(".publish_field");a.is_image==false?i.find(".file_set").show().find(".filename").html('<img src="'+
EE.PATH_CP_GBL_IMG+'default.png" alt="'+EE.PATH_CP_GBL_IMG+'default.png" /><br />'+a.name):i.find(".file_set").show().find(".filename").html('<img src="'+a.thumb+'" alt="'+a.name+'" /><br />'+a.name);$("input[name="+d+"_hidden]").val(a.name);$("select[name="+d+"_directory]").val(a.directory);$.ee_filebrowser.reset()}var b,h;$("#layout_group_submit").click(function(){EE.publish.save_layout();return false});$("#layout_group_remove").click(function(){EE.publish.remove_layout();return false});$("#layout_preview select").change(function(){EE.publish.change_preview_link()});
$("a.reveal_formatting_buttons").click(function(){$(this).parent().parent().children(".close_container").slideDown();$(this).hide();return false});$("#write_mode_header .reveal_formatting_buttons").hide();if(EE.publish.smileys==true){$("a.glossary_link").click(function(){$(this).parent().siblings(".glossary_content").slideToggle("fast");$(this).parent().siblings(".smileyContent .spellcheck_content").hide();return false});$("a.smiley_link").toggle(function(){$(this).parent().siblings(".smileyContent").slideDown("fast",
function(){$(this).css("display","")})},function(){$(this).parent().siblings(".smileyContent").slideUp("fast")});$(this).parent().siblings(".glossary_content, .spellcheck_content").hide();$(".glossary_content a").click(function(){var a=$(this).closest(".publish_field"),d=a.attr("id").replace("hold_field_","field_id_");a.find("#"+d).insertAtCursor($(this).attr("title"));return false})}if(EE.publish.autosave){var q=false;h=function(){if(!q){q=true;setTimeout(b,1E3*EE.publish.autosave.interval)}};b=
function(){var a;if($("#tools:visible").length===1)h();else{a=$("#publishForm").serialize();$.ajax({type:"POST",dataType:"json",url:EE.BASE+"&C=content_publish&M=autosave",data:a,success:function(d){if(d.error)console.log(d.error);else if(d.success){d.autosave_entry_id&&$("input[name=autosave_entry_id]").val(d.autosave_entry_id);$("#autosave_notice").text(d.success)}else console.log("Autosave Failed");q=false}})}};var m=$("textarea, input").not(":password,:checkbox,:radio,:submit,:button,:hidden"),
o=$("select, :checkbox, :radio, :file");m.bind("keypress change",h);o.bind("change",h)}if(EE.publish.pages){m=$("#pages__pages_uri");var k=EE.publish.pages.pagesUri;m.val()||m.val(k);m.focus(function(){this.value===k&&$(this).val("")}).blur(function(){this.value===""&&$(this).val(k)})}$.ee_filebrowser();var j="";EE.publish.show_write_mode===true&&$("#write_mode_textarea").markItUp(myWritemodeSettings);EE.publish.markitup.fields!==undefined&&$.each(EE.publish.markitup.fields,function(a){$("#"+a).markItUp(mySettings)});
write_mode_height=$(window).height()-117;$("#write_mode_writer").css("height",write_mode_height+"px");$("#write_mode_writer textarea").css("height",write_mode_height-67-17+"px");var f=$(".write_mode_trigger").overlay({mask:{color:"#262626",loadSpeed:200,opacity:0.85},onBeforeLoad:function(){var a=this.getTrigger()[0],d=$("#write_mode_textarea");j=a.id.match(/^id_\d+$/)?"field_"+a.id:a.id.replace(/id_/,"");d.val($("#"+j).val());d.focus()},top:"center",closeOnClick:false});$(".publish_to_field").click(function(){var a=
"#"+j.replace(/field_/,"");a=$(".write_mode_trigger").index(a);$("#"+j).val($("#write_mode_textarea").val());f.eq(a).overlay().close();return false});$(".closeWindowButton").click(function(){var a="#"+j.replace(/field_/,"");a=$(".write_mode_trigger").index(a);f.eq(a).overlay().close();return false});var p=false;$.ee_filebrowser.add_trigger(".btn_img a, .file_manipulate",function(a){var d,i="",l="",s="",r="";textareaId=$(this).closest("#markItUpWrite_mode_textarea").length?"write_mode_textarea":$(this).closest(".publish_field").attr("id").replace("hold_field_",
"field_id_");if(textareaId!=undefined){d=$("#"+textareaId);d.focus()}if(a.is_image){l=EE.upload_directories[a.directory].properties;s=EE.upload_directories[a.directory].pre_format;r=EE.upload_directories[a.directory].post_format;i=EE.filebrowser.image_tag.replace(/src="(.*)\[!\[Link:!:http:\/\/\]!\](.*)"/,'src="$1{filedir_'+a.directory+"}"+a.name+'$2"');i=i.replace(/\/?>$/,a.dimensions+" "+l+" />");i=s+i+r}else{l=EE.upload_directories[a.directory].file_properties;s=EE.upload_directories[a.directory].file_pre_format;
s+='<a href="{filedir_'+a.directory+"}"+a.name+'" '+l+" >";r="</a>";r+=EE.upload_directories[a.directory].file_post_format}if(d.is("textarea")){if(!d.is(".markItUpEditor")){d.markItUp(myNobuttonSettings);d.closest(".markItUpContainer").find(".markItUpHeader").hide();d.focus()}a.is_image?$.markItUp({replaceWith:i}):$.markItUp({key:"L",name:"Link",openWith:s,closeWith:r,placeHolder:a.name})}else d.val(function(t,n){n+=s+i+r;return c(n)});$.ee_filebrowser.reset()});$("input[type=file]","#publishForm").each(function(){var a=
$(this).closest(".publish_field"),d=a.find(".choose_file");$.ee_filebrowser.add_trigger(d,$(this).attr("name"),e);a.find(".remove_file").click(function(){a.find("input[type=hidden]").val("");a.find(".file_set").hide();return false})});$(".hide_field span").click(function(){var a=$(this).parent().parent().attr("id").substr(11),d=$("#hold_field_"+a);a=$("#sub_hold_field_"+a);if(a.css("display")=="block"){a.slideUp();d.find(".ui-resizable-handle").hide();d.find(".field_collapse").attr("src",EE.THEME_URL+
"images/field_collapse.png")}else{a.slideDown();d.find(".ui-resizable-handle").show();d.find(".field_collapse").attr("src",EE.THEME_URL+"images/field_expand.png")}return false});$(".close_upload_bar").toggle(function(){$(this).parent().children(":not(.close_upload_bar)").hide();$(this).children("img").attr("src",EE.THEME_URL+"publish_plus.png")},function(){$(this).parent().children().show();$(this).children("img").attr("src",EE.THEME_URL+"publish_minus.gif")});$(".ping_toggle_all").toggle(function(){$("input.ping_toggle").each(function(){this.checked=
false})},function(){$("input.ping_toggle").each(function(){this.checked=true})});if(EE.user.can_edit_html_buttons){$(".markItUp ul").append('<li class="btn_plus"><a title="'+EE.lang.add_new_html_button+'" href="'+EE.BASE+"&C=myaccount&M=html_buttons&id="+EE.user_id+'">+</a></li>');$(".btn_plus a").click(function(){return confirm(EE.lang.confirm_exit,"")})}$(".markItUpHeader ul").prepend('<li class="close_formatting_buttons"><a href="#"><img width="10" height="10" src="'+EE.THEME_URL+'images/publish_minus.gif" alt="Close Formatting Buttons"/></a></li>');
$(".close_formatting_buttons a").toggle(function(){$(this).parent().parent().children(":not(.close_formatting_buttons)").hide();$(this).parent().parent().css("height","13px");$(this).children("img").attr("src",EE.THEME_URL+"images/publish_plus.png")},function(){$(this).parent().parent().children().show();$(this).parent().parent().css("height","22px");$(this).children("img").attr("src",EE.THEME_URL+"images/publish_minus.gif")});$(".tab_menu li:first").addClass("current");EE.publish.title_focus==true&&
$("#title").focus();EE.publish.which=="new"&&$("#title").bind("keyup blur",liveUrlTitle);EE.publish.versioning_enabled=="n"?$("#revision_button").hide():$("#versioning_enabled").click(function(){$(this).attr("checked")?$("#revision_button").show():$("#revision_button").hide()});EE.publish.category_editor();if(EE.publish.hidden_fields){EE._hidden_fields=[];var g=$("input");$.each(EE.publish.hidden_fields,function(a){EE._hidden_fields.push(g.filter("[name="+a+"]")[0])});$(EE._hidden_fields).after('<p class="hidden_blurb">This module field only shows in certain circumstances. This is a placeholder to let you define it in your layout.</p>')}});
