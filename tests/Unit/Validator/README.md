# Validator Unit Tests

This directory contains unit tests for the validator check classes used to validate PHP stubs against reflection data.

## Test Files

### CheckTestCase.php
Base test case providing helper methods for creating mock entities:
- `createMockStorageManager()` - Mock ParsedDataStorageManager
- `createMockFunction()` - Mock PHPFunction with parameters and return types
- `createMockClass()` - Mock PHPClass with methods
- `createMockMethod()` - Mock PHPMethod with parameters and return types
- `createMockParameter()` - Mock PHPParameter with name and type
- `createType()` - Create StandaloneType instances
- `createMockType()` - Create mock type objects with string representation

### FunctionExistsCheckTest.php
Tests for `FunctionExistsCheck` validator:
- ✅ PHP version support validation
- ✅ Function exists in stubs (success case)
- ✅ Function not found in stubs (failure case)
- ✅ Finding functions among multiple functions
- ✅ Empty functions array handling
- ✅ Function lookup by getId() and getName()

**Status**: 7 tests, all passing

### ClassExistsCheckTest.php
Tests for `ClassExistsCheck` validator:
- ✅ PHP version support validation
- ✅ Class exists in stubs (success case)
- ✅ Class not found in stubs (failure case)
- ✅ Namespaced class handling
- ✅ Error message PHP version validation

**Status**: 5 tests, all passing

### MethodExistsCheckTest.php
Tests for `MethodExistsCheck` validator:
- ✅ PHP version support validation
- ✅ Method exists in class (success case)
- ✅ Method not found in class (failure case)
- ✅ Class not found in stubs (failure case)
- ✅ Invalid method ID format handling
- ✅ Namespaced class and method handling
- ✅ Finding methods among multiple methods
- ✅ Method lookup by getId() and getName()

**Status**: 10 tests, all passing

### ParameterNamesCheckTest.php
Tests for `ParameterNamesCheck` validator:
- ✅ PHP version support (8.0+)
- ⚠️ Other tests skipped - requires refactoring

**Status**: 1 test passing, 6 skipped
**Note**: Full testing requires refactoring `ParameterNamesCheck` to accept reflection manager as a dependency instead of using `Runner::getReflection()` static call.

### ParameterTypesCheckTest.php
Tests for `ParameterTypesCheck` validator:
- ✅ PHP version support (7.0+)
- ⚠️ Other tests skipped - requires refactoring

**Status**: 1 test passing, 7 skipped
**Note**: Full testing requires refactoring `ParameterTypesCheck` to accept reflection manager as a dependency instead of using `Runner::getReflection()` static call.

### ReturnTypesCheckTest.php
Tests for `ReturnTypesCheck` validator:
- ✅ PHP version support (7.0+)
- ⚠️ Other tests skipped - requires refactoring

**Status**: 1 test passing, 8 skipped
**Note**: Full testing requires refactoring `ReturnTypesCheck` to accept reflection manager as a dependency instead of using `Runner::getReflection()` static call.

## Running Tests

Run all validator unit tests:
```bash
vendor/bin/phpunit tests/Unit/Validator/
```

Run a specific test file:
```bash
vendor/bin/phpunit tests/Unit/Validator/FunctionExistsCheckTest.php
```

Run with test documentation:
```bash
vendor/bin/phpunit tests/Unit/Validator/ --testdox
```

## Test Coverage Summary

| Validator Check | Tests | Passing | Skipped | Status |
|----------------|-------|---------|---------|--------|
| FunctionExistsCheck | 7 | 7 | 0 | ✅ Complete |
| ClassExistsCheck | 5 | 5 | 0 | ✅ Complete |
| MethodExistsCheck | 10 | 10 | 0 | ✅ Complete |
| ParameterNamesCheck | 7 | 1 | 6 | ⚠️ Requires refactoring |
| ParameterTypesCheck | 8 | 1 | 7 | ⚠️ Requires refactoring |
| ReturnTypesCheck | 9 | 1 | 8 | ⚠️ Requires refactoring |
| **Total** | **46** | **25** | **21** | **54% Complete** |

## Future Improvements

### Refactoring for Better Testability

Three validator classes currently have limited test coverage due to their dependency on `Runner::getReflection()` static calls:
- `ParameterNamesCheck`
- `ParameterTypesCheck`
- `ReturnTypesCheck`

**Recommended refactoring approaches:**

1. **Constructor Dependency Injection** (Recommended)
   ```php
   class ParameterNamesCheck implements CheckInterface
   {
       private ParsedDataStorageManager $reflectionManager;

       public function __construct(ParsedDataStorageManager $reflectionManager)
       {
           $this->reflectionManager = $reflectionManager;
       }

       public function run(ParsedDataStorageManager $stubs, string $entityId, string $phpVersion): CheckResultSet
       {
           // Use $this->reflectionManager instead of Runner::getReflection($phpVersion)
       }
   }
   ```

2. **Runner Wrapper** (Alternative)
   Create a `RunnerInterface` that can be mocked in tests.

3. **Method Parameter** (Simplest)
   Pass reflection manager as a parameter to the `run()` method.

### Additional Test Scenarios

Once refactoring is complete, add tests for:
- Union types handling
- Intersection types handling
- Nullable types handling
- Mixed type handling
- Parameter with default values
- Variadic parameters
- Pass by reference parameters
- Complex type mismatches
- Edge cases with empty parameter lists

## Benefits of These Unit Tests

1. **Fast Execution**: Tests run in ~0.08 seconds (no file I/O or parsing)
2. **Isolated**: Each test validates only the validator logic
3. **Maintainable**: Mock data is easy to understand and modify
4. **Comprehensive**: Cover success cases, failure cases, and edge cases
5. **Debugging**: Failed tests immediately identify the specific scenario that broke
6. **Regression Prevention**: Catch bugs before they reach integration tests

## Integration with CI/CD

These unit tests should run as part of the test suite:
- Fast feedback during development
- Pre-commit validation
- CI pipeline checks
- Complement (not replace) integration tests in `FunctionValidatorTest.php`
