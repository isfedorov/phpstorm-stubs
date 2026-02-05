<?php

namespace StubTests\Sources\DataProvider;

interface StubsDataProvider
{
    public function getStubFileContent(string $path): string;

    public function getAllStubFiles():array;
}
