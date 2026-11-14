# Jumbo Coding Standard - Line Length

The soft line length limit in the [Jumbo Coding Standard](../README.md) is 120. To use a different limit, override it in your project's
`phpcs.xml` (see [Usage](../README.md#usage)).

A line over the limit gets a warning, `Generic.Files.LineLength.MaxExceeded`, not an error, as PER Coding Style 3.1
requires a soft limit. Its message says "maximum limit", as the sniff's own wording can't be changed from a ruleset.
`phpcs` still exits with a non-zero code on warnings, unless your project sets `ignore_warnings_on_exit`.

## The 80-Character Recommendation

PER also says lines SHOULD NOT be longer than 80 characters. A line over 80, but within the limit, gets a
`Generic.Files.LineLength.TooLong` warning with a severity of 4. `phpcs` only shows warnings with a severity of at least
5 by default, so it's hidden, and doesn't affect the exit code. To see these lines:

```bash
vendor/bin/phpcs --warning-severity=4
```

Neither limit is auto-fixable, as only a person can choose where to split a line. To change the recommended limit, set
`lineLimit` on `Generic.Files.LineLength`. To turn the check off, exclude `Generic.Files.LineLength.TooLong`.

## Changing the Limit

In the [PERCS31](PERCS31.md) standard, only `Generic.Files.LineLength` uses the limit, so set its `absoluteLineLimit` to change it.
That property holds the soft limit, as the sniff's `lineLimit` holds the recommended 80. The rest of this section is
about the Jumbo standard.

Fourteen sniffs check or wrap long lines, and each has its own line-length property. The Jumbo ruleset sets all fourteen
from two XML entities, `lineLength` and `lineLengthPlusOne`, at the top of its `ruleset.xml`. An entity only applies
within the file that defines it, so you can't override it from `phpcs.xml`. Set all fourteen properties instead:

| Sniff                                                                      | Property            | Value      |
| -------------------------------------------------------------------------- | ------------------- | ---------- |
| `Generic.Files.LineLength`                                                 | `absoluteLineLimit` | length     |
| `Jumbo.Classes.RequireSingleLineMethodSignature`                           | `maxLineLength`     | length     |
| `SlevomatCodingStandard.Classes.RequireMultiLineMethodSignature`           | `minLineLength`     | length + 1 |
| `SlevomatCodingStandard.ControlStructures.RequireSingleLineCondition`      | `maxLineLength`     | length     |
| `SlevomatCodingStandard.ControlStructures.RequireMultiLineCondition`       | `minLineLength`     | length + 1 |
| `SlevomatCodingStandard.ControlStructures.RequireMultiLineTernaryOperator` | `lineLengthLimit`   | length     |
| `Jumbo.Functions.RequireSingleLineCall`                                    | `maxLineLength`     | length     |
| `SlevomatCodingStandard.Functions.RequireMultiLineCall`                    | `minLineLength`     | length + 1 |
| `Jumbo.Arrays.ArrayDeclaration`                                            | `maxLineLength`     | length     |
| `Jumbo.ControlStructures.RequireSingleLineHeader`                          | `maxLineLength`     | length     |
| `Jumbo.ControlStructures.MatchArmWrapIndent`                               | `maxLineLength`     | length     |
| `Jumbo.Formatting.RequireSingleLineStatement`                              | `maxLineLength`     | length     |
| `Jumbo.Functions.RequireSingleLineSignature`                               | `maxLineLength`     | length     |
| `Jumbo.Classes.RequireSingleLineClassHeader`                               | `maxLineLength`     | length     |

If you set only some of them, the fixers can create lines over your limit, or leave lines short that could be joined.

In this example `phpcs.xml`, two entities keep the values in step. XML can't do arithmetic, so the "plus one" value is
set separately:

```xml
<?xml version="1.0"?>
<!DOCTYPE ruleset [
    <!ENTITY lineLength "100">
    <!ENTITY lineLengthPlusOne "101">
]>
<ruleset name="My Project">
    <description>Coding standard for my project</description>

    <file>src</file>
    <file>tests</file>

    <rule ref="Jumbo"/>

    <rule ref="Generic.Files.LineLength">
        <properties>
            <property name="absoluteLineLimit" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="Jumbo.Classes.RequireSingleLineMethodSignature">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="SlevomatCodingStandard.Classes.RequireMultiLineMethodSignature">
        <properties>
            <property name="minLineLength" value="&lineLengthPlusOne;"/>
        </properties>
    </rule>
    <rule ref="SlevomatCodingStandard.ControlStructures.RequireSingleLineCondition">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="SlevomatCodingStandard.ControlStructures.RequireMultiLineCondition">
        <properties>
            <property name="minLineLength" value="&lineLengthPlusOne;"/>
        </properties>
    </rule>
    <rule ref="SlevomatCodingStandard.ControlStructures.RequireMultiLineTernaryOperator">
        <properties>
            <property name="lineLengthLimit" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="Jumbo.Functions.RequireSingleLineCall">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="SlevomatCodingStandard.Functions.RequireMultiLineCall">
        <properties>
            <property name="minLineLength" value="&lineLengthPlusOne;"/>
        </properties>
    </rule>
    <rule ref="Jumbo.Arrays.ArrayDeclaration">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="Jumbo.ControlStructures.RequireSingleLineHeader">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="Jumbo.ControlStructures.MatchArmWrapIndent">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="Jumbo.Formatting.RequireSingleLineStatement">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="Jumbo.Functions.RequireSingleLineSignature">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="Jumbo.Classes.RequireSingleLineClassHeader">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
</ruleset>
```
