<?php

namespace EasyAPI;
use EasyAPI\Request;

abstract class Middleware
{
    abstract public function handle(Request $request): Request;
}