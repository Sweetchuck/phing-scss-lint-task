# phing-scss-lint-task

This project is a [Phing](https://www.phing.info) build tool task for running [scss-lint](https://github.com/causes/scss-lint)


``` xml
<taskdef name="scss-lint" classname="path-to.phing-scss-lint-task.ScssLintTask"/>
<target name="my-lint-target">
  <scss-lint exclude="*.css" haltOnError="true" haltOnWarning="false" format="Default">
    <param>path/to/sass</param>
  </scss-lint>
</target>
```
