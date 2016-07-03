<?php

namespace PHPSTORM_META {
    $STATIC_METHOD_TYPES = [
        \PHPUnit_Framework_TestCase::createMock('') => [
            "" == "@|PHPUnit_Framework_MockObject_MockObject",
        ],
        \PHPUnit_Framework_TestCase::getMock('') => [
            "" == "@|PHPUnit_Framework_MockObject_MockObject",
        ],
        \Psr\Http\Message\ServerRequestInterface::getAttribute('') => [
            "" == "@",
        ],
    ];
}
