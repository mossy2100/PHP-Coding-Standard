# Mossy2100 Coding Standard - Line Length

The line length limit in the [Mossy2100 Coding Standard](../README.md) is 120. To use a different limit, override it in your project's
`phpcs.xml` (see [Usage](../README.md#usage)).

Ten sniffs check or wrap long lines, and each has its own line-length property. The standard sets all ten from an XML
entity, `lineLength`, at the top of its `ruleset.xml`. An entity only applies within the file that defines it, so you
can't override it from `phpcs.xml`. Set all ten properties instead:

| Sniff                                                                      | Property          | Value      |
| -------------------------------------------------------------------------- | ----------------- | ---------- |
| `SlevomatCodingStandard.Files.LineLength`                                  | `lineLengthLimit` | length     |
| `SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature`          | `maxLineLength`   | length     |
| `SlevomatCodingStandard.Classes.RequireMultiLineMethodSignature`           | `minLineLength`   | length + 1 |
| `SlevomatCodingStandard.ControlStructures.RequireSingleLineCondition`      | `maxLineLength`   | length     |
| `SlevomatCodingStandard.ControlStructures.RequireMultiLineCondition`       | `minLineLength`   | length + 1 |
| `SlevomatCodingStandard.ControlStructures.RequireMultiLineTernaryOperator` | `lineLengthLimit` | length     |
| `SlevomatCodingStandard.Functions.RequireSingleLineCall`                   | `maxLineLength`   | length     |
| `SlevomatCodingStandard.Functions.RequireMultiLineCall`                    | `minLineLength`   | length + 1 |
| `Mossy2100.Arrays.ArrayDeclaration`                                        | `maxLineLength`   | length     |
| `Mossy2100.ControlStructures.RequireSingleLineHeader`                      | `maxLineLength`   | length     |

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

    <rule ref="Mossy2100"/>

    <rule ref="SlevomatCodingStandard.Files.LineLength">
        <properties>
            <property name="lineLengthLimit" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature">
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
    <rule ref="SlevomatCodingStandard.Functions.RequireSingleLineCall">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="SlevomatCodingStandard.Functions.RequireMultiLineCall">
        <properties>
            <property name="minLineLength" value="&lineLengthPlusOne;"/>
        </properties>
    </rule>
    <rule ref="Mossy2100.Arrays.ArrayDeclaration">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
    <rule ref="Mossy2100.ControlStructures.RequireSingleLineHeader">
        <properties>
            <property name="maxLineLength" value="&lineLength;"/>
        </properties>
    </rule>
</ruleset>
```
