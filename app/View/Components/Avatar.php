<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Avatar extends Component
{
    public $user;

    public int $size;

    public function __construct($user, $size = 40)
    {
        $this->user = $user;
        $this->size = $size;
    }

    public function render(): View|Closure|string
    {
        return view('components.avatar');
    }
}