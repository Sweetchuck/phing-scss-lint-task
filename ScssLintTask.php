<?php

/**
 * @file
 * Documentation missing.
 */

require_once "phing/Task.php";

/**
 * Class ScssLintParam.
 */
class ScssLintParam
{
    /**
     * @var string
     */
    protected $text;

    /**
     * @param string $text
     */
    public function addText($text)
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }
}

/**
 * Class ScssLintRequire.
 */
class ScssLintRequire extends ScssLintParam
{
}

/**
 * Class ScssLintTask.
 */
class ScssLintTask extends Task
{

    /**
     * No lints were found.
     */
    const EXIT_OK = 0;

    /**
     * Lints with a severity of warning were reported (no errors).
     */
    const EXIT_LINT_WARNING = 1;

    /**
     * One or more errors were reported (and any number of warnings).
     */
    const EXIT_LINT_ERROR = 2;

    /**
     * Command line usage error (invalid flag, etc.).
     */
    const EXIT_CLI_USAGE_ERROR = 64;

    /**
     * One or more files specified were not found.
     */
    const EXIT_FILE_NOT_FOUND = 66;

    /**
     * Unexpected error (i.e. a bug); please report it.
     */
    const EXIT_UNEXPECTED_ERROR = 70;

    /**
     * Invalid configuration file; your YAML is likely incorrect.
     */
    const EXIT_INVALID_CONFIG = 78;

    /**
     * @var boolean
     */
    protected $haltOnWarning = FALSE;

    /**
     * @var boolean
     */
    protected $haltOnError = FALSE;

    /**
     * @var string
     */
    protected $returnProperty = '';

    /**
     * @var string
     */
    protected $outputProperty = '';

    /**
     * @var string
     */
    protected $dir = '';

    /**
     * @var string
     */
    protected $executable = 'scss-lint';

    /**
     * All fileset objects assigned to this task.
     *
     * @var FileSet[]
     */
    protected $fileSets = array();

    /**
     * All fileset objects assigned to this task.
     *
     * @var ScssLintRequire[]
     */
    protected $requiredLibraries = array();

    /**
     * @var array
     */
    protected $options = array(
        'format' => '',
        'include-linter' => '',
        'exclude-linter' => '',
        'config' => '',
        'exclude' => '',
        'out' => '',
    );

    /**
     * All fileset objects assigned to this task.
     *
     * @var ScssLintParam[]
     */
    protected $parameters = array();

    /**
     * @var string
     */
    protected $commandPattern = '';

    /**
     * @var array
     */
    protected $commandArgs = array();

    /**
     * @var int
     */
    protected $commandExitCode = 0;

    /**
     * @var array
     */
    protected $commandOutput = array();

    /**
     * Initialize the scss-lint executable.
     */
    public function init()
    {
        $executable = $this->project->getProperty('scss-lint.executable');
        if ($executable) {
            $this->setExecutable($executable);
        }

        return $this;
    }

    /**
     * @param boolean $value
     */
    public function setHaltOnWarning($value)
    {
        $this->haltOnWarning = (boolean) $value;
    }

    /**
     * @param boolean $value
     */
    public function setHaltOnError($value)
    {
        $this->haltOnError = (boolean) $value;
    }

    /**
     * @param string $value
     */
    public function setReturnProperty($value)
    {
        $this->returnProperty = $value;
    }

    /**
     * @param string $value
     */
    public function setOutputProperty($value)
    {
        $this->outputProperty = $value;
    }

    /**
     * @param string $value
     */
    public function setDir($value)
    {
        $this->dir = $value;
    }

    /**
     * @param string $value
     */
    public function setExecutable($value)
    {
        $this->executable = $value;
    }

    /**
     * @param string $value
     */
    public function setFormat($value)
    {
        $this->options['format'] = $value;
    }

    /**
     * @param string $value
     */
    public function setIncludeLinter($value)
    {
        $this->options['include-linter'] = $value;
    }

    /**
     * @param string $value
     */
    public function setExcludeLinter($value)
    {
        $this->options['exclude-linter'] = $value;
    }

    /**
     * @param string $value
     */
    public function setConfig($value)
    {
        $this->options['config'] = $value;
    }

    /**
     * @param string $value
     */
    public function setExclude($value)
    {
        $this->options['exclude'] = $value;
    }

    /**
     * File to save error messages to.
     *
     * @param PhingFile $value
     * @internal param PhingFile $file
     */
    public function setOut(PhingFile $value)
    {
        $this->options['out'] = $value;
    }

    /**
     * Nested adder, adds a set of files (nested fileset attribute).
     *
     * @param FileSet $file_set
     */
    public function addFileSet(FileSet $file_set)
    {
        $this->fileSets[] = $file_set;
    }

    /**
     * @param ScssLintRequire $require
     */
    public function addRequire(ScssLintRequire $require)
    {
        $this->requiredLibraries[$require->getText()] = $require;
    }

    /**
     * @param ScssLintParam $param
     */
    public function addParam(ScssLintParam $param)
    {
        $this->parameters[$param->getText()] = $param;
    }

    /**
     *  {@inheritdoc}
     */
    public function main()
    {
        $this
            ->validate()
            ->executePrepare()
            ->execute()
            ->executePost();
    }

    /**
     * @param string $library_name
     */
    protected function requiredLibrariesAdd($library_name) {
        $this->requiredLibraries[$library_name] = new ScssLintRequire();
        $this->requiredLibraries[$library_name]->addText($library_name);
    }

    /**
     * @return $this
     *
     * @throws BuildException
     */
    protected function validate()
    {
        if (!trim($this->executable)) {
            throw new BuildException('scss-lint executable is missing.');
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function executePrepare() {
        $this->commandPattern = '';
        $this->commandArgs = array();

        foreach ($this->options as $name => $value) {
            if ($value) {
                $this->commandPattern .= ' --' . $name . '=%s';
                $this->commandArgs[] = $value;
            }
        }

        if ($this->options['format'] === 'Checkstyle') {
            $this->requiredLibrariesAdd('scss_lint_reporter_checkstyle');
        }

        foreach ($this->requiredLibraries as $required) {
            $this->commandPattern .= ' --require=%s';
            $this->commandArgs[] = $required->getText();
        }

        $this->commandPattern .= str_repeat(" \\\n  %s", count($this->parameters));
        foreach ($this->parameters as $parameter) {
            $this->commandArgs[] = $parameter->getText();
        }

        $project = $this->getProject();
        foreach ($this->fileSets as $fileset) {
            $dir_scanner = $fileset->getDirectoryScanner($project);
            /** @var array $files */
            $files = $dir_scanner->getIncludedFiles();
            $dir = $fileset->getDir($project)->getPath();

            $this->commandPattern .= str_repeat(" \\\n  %s", count($files));
            foreach ($files as $file) {
                $this->commandArgs[] = $dir . DIRECTORY_SEPARATOR . $file;
            }
        }

        return $this;
    }

    /**
     * @return $this
     *
     * @throws BuildException
     */
    protected function execute() {
        $this->commandPattern = '%s ' . $this->commandPattern;
        foreach (array_keys($this->commandArgs) as $key) {
            $this->commandArgs[$key] = escapeshellarg($this->commandArgs[$key]);
        }
        array_unshift($this->commandArgs, escapeshellcmd($this->executable));

        if ($this->dir && !chdir($this->dir)) {
            throw new BuildException('Working directory is not exists.');
        }

        $command = vsprintf($this->commandPattern, $this->commandArgs);
        $this->log($command);
        exec($command, $this->commandOutput, $this->commandExitCode);

        if ($this->dir) {
            chdir($this->project->getBasedir());
        }

        return $this;
    }

    /**
     * @return $this
     *
     * @throws BuildException
     */
    protected function executePost() {
        if ($this->returnProperty) {
            $this->project->setProperty($this->returnProperty, $this->commandExitCode);
        }

        if ($this->outputProperty) {
            $this->project->setProperty($this->outputProperty, implode("\n", $this->commandOutput));
        }

        foreach ($this->commandOutput as $line) {
            $this->log($line);
        }

        $message = $this->commandExitMessage();
        switch ($this->commandExitCode) {
            case static::EXIT_OK:
                $this->log($message, Project::MSG_INFO);
                break;

            case static::EXIT_LINT_WARNING:
                if ($this->haltOnWarning) {
                    throw new BuildException($message);
                }
                break;

            case static::EXIT_LINT_ERROR:
                if ($this->haltOnError) {
                    throw new BuildException($message);
                }
                break;

            default:
                throw new BuildException($message);

        }

        return $this;
    }

    /**
     * @return string
     */
    protected function commandExitMessage() {
        $messages = array(
            static::EXIT_OK => 'No lint found',
            static::EXIT_LINT_WARNING => 'Lint warnings in SCSS files',
            static::EXIT_LINT_ERROR => 'Lint errors in SCSS files',
            static::EXIT_FILE_NOT_FOUND => 'One or more files specified were not found',
            static::EXIT_CLI_USAGE_ERROR => 'Command line usage error (invalid flag, etc.).',
            static::EXIT_INVALID_CONFIG => 'Invalid configuration file; your YAML is likely incorrect',
            static::EXIT_UNEXPECTED_ERROR => 'Unexpected error (i.e. a bug).',
        );

        return isset($messages[$this->commandExitCode]) ? $messages[$this->commandExitCode] : 'Unknown error.';
    }

}
