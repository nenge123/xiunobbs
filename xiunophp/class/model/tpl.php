<?php

namespace model;

use MyApp;

class tpl
{
	/**
	 * 一个表单项
	 * @param string $name 表单名
	 * @param mixed $value 表单值
	 * @param string $placeholder 提示文字
	 * @param string $inputclass 样式名
	 * @param mixed $id id
	 * @param string $more 更多
	 * @param string $type 表单类型
	 * @return string HTML片段
	 * @param string $labelclass label样式名
	 */
	public static function input(
		string $name = '',
		mixed $value = '',
		string $placeholder = '',
		string $more = '',
		string $inputclass = '',
		?string $id = null,
		string $type = 'text',
		?string $labelclass = null
	): string {
		if (empty($id)):
			$id = 'input-' . dechex(rand(1000, 9999)) . dechex(rand(1000, 9999));
		endif;
		return '<input type="' . $type . '" class="form-control ' . $inputclass . '" id="' . $id . '" name="' . $name . '" value="' . $value . '"' . (!empty($placeholder) ? ' placeholder="' . $placeholder . '"' : '') . ' ' . $more . '>' . (!empty($placeholder) ? '<label for="' . $id . '" class="' . ($labelclass ?: '') . '">' . $placeholder . '</label>' : '');
	}
	/**
	 * 一个表单文本项
	 */
	public static function char(
		string $name = '',
		mixed $value = '',
		string $placeholder = '',
		string $more = '',
		string $inputclass = '',
		?string $id = null,
		?string $labelclass = null
	): string {
		if (empty($id)):
			$id = 'text-' . dechex(rand(1000, 9999)) . dechex(rand(1000, 9999));
		endif;
		return self::input($name,$value,$placeholder,$more,$inputclass,$id,'text',$labelclass);
	}
	/**
	 * 一个表单checkbox项
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param string $placeholder
	 * @param string $more
	 * @param string|null $id
	 * @return string
	 */
	public static function checkbox(
		string $name = '',
		mixed $checkvalue = '',
		string $placeholder = '',
		string $more = '',
		?string $id = null,
		mixed $value = '1',
	): string {
		if (empty($id)):
			$id = 'check-' . dechex(rand(1000, 9999)) . dechex(rand(1000, 9999));
		endif;
		if ($checkvalue == $value):
			$more .= ' checked';
		endif;
		return self::input($name, $value, $placeholder, $more, 'form-check-input', $id, 'checkbox', 'form-check-label');
	}
	/**
	 * 一个数字表单项
	 */
	public static function number(
		string $name = '',
		mixed $value = '',
		string $placeholder = '',
		string $more = '',
		?string $id = null,
		string $inputclass = '',
		string $labelclass = '',
	):string
	{
		if (empty($id)):
			$id = 'num-' . dechex(rand(1000, 9999)) . dechex(rand(1000, 9999));
		endif;
		return self::input($name, $value, $placeholder, $more, $inputclass, $id, 'number',$labelclass);
	}
	/**
	 * 一个密码表单项
	 */
	public static function password(
		string $name = '',
		mixed $value = '',
		string $placeholder = '',
		string $more = '',
		?string $id = null,
		string $inputclass = '',
		string $labelclass = '',
	):string
	{
		if (empty($id)):
			$id = 'pw-' . dechex(rand(1000, 9999)) . dechex(rand(1000, 9999));
		endif;
		return self::input($name, $value, $placeholder, $more, $inputclass, $id, 'password',$labelclass);
	}
	/**
	 * 邮件表单项
	 */
	public static function email(
		string $name = '',
		mixed $value = '',
		string $placeholder = '',
		string $more = '',
		?string $id = null,
		string $inputclass = '',
		string $labelclass = '',
	):string
	{
		if (empty($id)):
			$id = 'email-' . dechex(rand(1000, 9999)) . dechex(rand(1000, 9999));
		endif;
		return self::input($name, $value, $placeholder, $more, $inputclass, $id, 'email',$labelclass);
	}
	/**
	 * 日期表单项
	 */
	public static function date(
		string $name = '',
		mixed $value = '',
		string $placeholder = '',
		string $more = '',
		?string $id = null,
		string $inputclass = '',
		string $labelclass = '',
	):string
	{
		if (empty($id)):
			$id = 'date-' . dechex(rand(1000, 9999)) . dechex(rand(1000, 9999));
		endif;
		if(empty($placeholder)&&$name):
			$placeholder = MyApp::Lang($name);
		endif;
		$more .= ' max="'.date('Y-m-d',mktime(24,0,0,)).'"';
		return self::input($name, $value, $placeholder, $more, $inputclass, $id, 'date',$labelclass);
	}
	/**
	 * 日期表单项
	 */
	public static function fulltime(
		string $name = '',
		mixed $value = '',
		string $placeholder = '',
		string $more = '',
		?string $id = null,
		string $inputclass = '',
		string $labelclass = '',
	):string
	{
		if (empty($id)):
			$id = 'time-' . dechex(rand(1000, 9999)) . dechex(rand(1000, 9999));
		endif;
		if(empty($placeholder)&&$name):
			$placeholder = MyApp::Lang($name);
		endif;
		$more .= ' max="'.date('Y-m-d H:i:s').'" onmethods="datetimepicker" data-provide="datetimepicker" data-side-by-side="true" data-format="YYYY-MM-DD HH:mm:ss"';
		return self::input($name, $value, $placeholder, $more, $inputclass, $id, 'text',$labelclass);
	}
	/**
	 * ip表单项
	 */
	public static function ipv4(
		string $name = '',
		mixed $value = '',
		string $placeholder = '',
		string $more = '',
		?string $id = null,
		string $inputclass = '',
		string $labelclass = '',
	):string
	{
		if (empty($id)):
			$id = 'ipv4-' . dechex(rand(1000, 9999)) . dechex(rand(1000, 9999));
		endif;
		if(empty($placeholder)&&$name):
			$placeholder = MyApp::Lang($name);
		endif;
		$more .= ' pattern="^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?).(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?).(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?).(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$"';
		return self::input($name, $value, $placeholder, $more, $inputclass, $id, 'text',$labelclass);
	}
	/**
	 * 下拉表单项
	 */
	public static function select(
		array $list,
		mixed $value='',
		string $name = '',
		string $placeholder = '',
		string $inputclass = '',
		string $optionclass = '',
		?string $labelclass = null,
	):string {
		$html = '';
		if (empty($id)):
			$id = 'select-' . dechex(rand(1000, 9999)) . dechex(rand(1000, 9999));
		endif;
		foreach ($list as $k => $v):
			$html .= '<option value="' . $k . '" class="' . $optionclass . '"' . ($k == $value ? ' selected' : '') . '>' . $v . '</option>';
		endforeach;
		return '<select id="' . $id . '" name="' . $name . '" class="form-select ' . $inputclass . '">' . $html . '</select>' . (!empty($placeholder) ? '<label for="' . $id . '" class="' . ($labelclass ?: '') . '">' . $placeholder . '</label>' : '');;
	}
	/**
	 * 文本表单项
	 * @param string $name
	 * @param mixed $value
	 * @param string $placeholder
	 * @param string $more
	 * @param string|null $inputclass
	 * @param string|null $id
	 * @param string|null $labelclass
	 * @return string
	 */
	public static function textarea(
		string $name = '',
		mixed $value = '',
		string $placeholder = '',
		string $more = '',
		?string $id = null,
		string $inputclass = '',
		?string $labelclass = null

	): string {
		if (empty($id)):
			$id = 'txt-' . dechex($_SERVER['REQUEST_TIME']) . dechex(rand(1000, 9999));
		endif;
		return '<textarea class="form-control ' . $inputclass . '" id="' . $id . '" name="' . $name . '"' . (!empty($placeholder) ? ' placeholder="' . $placeholder . '"' : '') . ' ' . $more . '>' . htmlentities($value, ENT_HTML5) . '</textarea>' . (!empty($placeholder) ? '<label for="' . $id . '" class="' . ($labelclass ?: '') . '">' . $placeholder . '</label>' : '');
	}
	/**
	 * 返回一个 设置表单text项
	 */
	public static function conf_char(string $name, string $more = '', ?string $lang = null,mixed $default=''): string
	{

		if (empty($lang)):
			$lang = $name;
		endif;
		$value = MyApp::conf($name,$default);
		return self::input($name,$value, MyApp::Lang($lang), $more, '', $name);
	}
	/**
	 * conf配置 数字表单
	 */
	public static function conf_num(string $name='', string $more='', ?string $lang = null): string
	{

		if (empty($lang)):
			$lang = $name;
		endif;
		return self::number($name,MyApp::conf($name), $lang?MyApp::Lang($lang):'', $more,$name);
	}
	/**
	 * conf配置 文本表单
	 */
	public static function conf_txt(string $name, ?string $lang = null, $more = 'style="min-height: 150px;"'): string
	{
		if (empty($lang)):
			$lang = $name;
		endif;
		return self::textarea($name, MyApp::conf($name), MyApp::Lang($lang), $more, $name);
	}
	/**
	 * conf配置 布尔值表单
	 */
	public static function conf_check(string $name, ?string $more = '', ?string $lang = null, $value = 1): string
	{
		if (empty($lang)):
			$lang = $name;
		endif;
		return self::checkbox($name, MyApp::conf($name), MyApp::Lang($lang), $more, $name, $value);
	}
	/**
	 * conf配置 下拉表单
	 */
	public static function conf_list_key(array $list, string $name, string $lang = ''): string
	{
		if (empty($lang)):
			$lang = $name;
		endif;
		return self::select($list, MyApp::conf($name), $name, MyApp::Lang($lang));
	}
	/**
	 * conf配置 下拉表单
	 */
	public static function conf_list_num(int $num, string $name, string $lang = ''): string
	{
		$list = [];
		for ($i = 0; $i <= $num; $i++):
			$list[$i] = MyApp::Lang($name . '_' . $i);
		endfor;
		if (empty($lang)):
			$lang = $name;
		endif;
		return self::select($list, MyApp::conf($name), $name, MyApp::Lang($lang));
	}
	/**
	 * conf配置 可选语言表单
	 */
	public static function conf_i18n(): string
	{
		$name = 'lang';
		$list = scandir(MyApp::path('lang/'));
		$list = array_filter($list, fn($m) => !str_contains($m, '.'));
		$newlist = array_combine($list, array_map(fn($m) => MyApp::Lang('lang_' . str_replace('-', '_', $m)), $list));
		return self::conf_list_key($newlist,$name);
	}
}
