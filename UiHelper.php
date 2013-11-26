<?php
/**
	 * 显示分页控件,根据总条数进行分页,控件居中显示
	 * @param String $prefix 分页控件的前缀（一般为链接）会将页号追加到连接最后面，所以确保连接格式正确，负责无法使用
	 * eg http://www.xxx.com/?aciont=log_query&playerId=16&page=1
	 * @param Number $curPage 当前所在页，默认为1
	 * @param Number $count 数据总数
	 * @param Number $pageSize 每页显示的数据条数
	 * @param String $firstText 首页的文字
	 * @param String $previousText 上一页的文字
	 * @param String $nextText 下一页的文字
	 * @param String $LastText 尾页的文字
	 */
	public static function printPagination($prefix,$curPage=1,$count,$pageSize=self::PAGE_SIZE_15,$firstText='|<<',$previousText="Prev",$nextText="Next",$LastText=">>|"){
		if($count<=$pageSize){
			return;
		}
		$pageCount=ceil($count/$pageSize); //总共的页数
		$leftCount=floor(self::MAX_PAGE_COUNT/2); //左侧的页签数目
		$rightCount=self::MAX_PAGE_COUNT-$leftCount; //右侧的页签数目
		$startPos=max(1,$curPage-$leftCount); //显示的起点
		$endPos=min($startPos+self::MAX_PAGE_COUNT,$pageCount); //显示的终点
		$startPos=max($endPos-self::MAX_PAGE_COUNT,1);
		
		$html='<div class="pagination pagination-centered"><ul>';
		$html.='<li><a title="'.$firstText.'" href="'.$prefix.'1">'.$firstText.'</a></li>'; //首页
		if($curPage>1){
			$html.='<li><a title="'.$previousText.'" href="'.$prefix.($curPage-1).'">'.$previousText.'</a></li>'; //上一页
		}
		for($i=$startPos;$i<=$endPos;$i++){
			if($i==$curPage){
				$html.='<li><a style="font-weight:bold;background-color:#51a351;color:#fff" href="'.$prefix.$i.'">'.$i.'</a></li>';
			}else{
				$html.='<li><a href="'.$prefix.$i.'">'.$i.'</a></li>';
			}
		}
		if($curPage<$pageCount){
			$html.='<li><a title="'.$nextText.'" href="'.$prefix.($curPage+1).'">'.$nextText.'</a></li>'; //下一页
		}
		$html.='<li><a title="'.$LastText.'" href="'.$prefix.$pageCount.'">'.$LastText.'</a></li>'; //最后一页
		$html.='</div>';
		echo $html;
	}
	/**
	 * 显示弹出提示
	 * @param String $icon 图标:默认为问号?
	 * @param String $position 显示位置 left/right/top/bottom
	 * @param String $title 标题
	 * @param String $info 内容
	 */
	public static function printPopover($icon='?',$placement='right',$title,$info){
		$html=<<<FUCK
		<a class="btn btn-mini btn-inverse"
			data-toggle="popover" data-placement="{$placement}" title=""
			data-original-title="{$title}"data-content="{$info}">{$icon}</a>
			<script>
				$("a[data-toggle=popover]").popover().click(function(e) {e.preventDefault()});
			</script>
FUCK;
		echo $html;
	}
?>