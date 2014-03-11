<?php

class HTML
{
	public static function link($path, $text)
	{
		return '<a href="'.escape_html(URL::to($path)).'">'.escape_html($text).'</a>';
	}
}