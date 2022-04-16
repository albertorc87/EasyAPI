<?php

namespace EasyAPI;
use EasyAPI\Request;

/**
 * Middleware base class
 */
abstract class Middleware
{
    abstract public function handle(Request $request): Request;
}