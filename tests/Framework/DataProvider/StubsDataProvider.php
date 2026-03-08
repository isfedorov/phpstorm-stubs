<?php

namespace StubTests\Sources\DataProvider;

interface StubsDataProvider
{
    public function getAllStubFiles(): array;

    public function getStubFileContent(string $path): string;

    public function getStubsRootPath(): string;
}
