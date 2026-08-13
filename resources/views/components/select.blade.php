@props([

'label',

'name',

'options'=>[],

'selected'=>null

])

<div class="mb-3">

<label class="form-label">

{{ $label }}

</label>

<select

name="{{ $name }}"

{{ $attributes->merge([

'class'=>'form-select'

]) }}

>

@foreach($options as $value=>$text)

<option

value="{{ $value }}"

@selected(old($name,$selected)==$value)>

{{ $text }}

</option>

@endforeach

</select>

@error($name)

<div class="text-danger small mt-1">

{{ $message }}

</div>

@enderror

</div>