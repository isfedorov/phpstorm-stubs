<?php

namespace StubTests\Sources\Parsers\Entities;

enum EntityType{

    case A_CLASS;
    case CONSTANT;
    case FUNCTION;
    case INTERFACE;
    case UNKNOWN;
    case ENUM;
}
