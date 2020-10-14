# XHGui Profiling

This option allows to profile Eventum page rendering and submit data to [XHGui].

For profiling data to be captured, you need to install and enable one of the
extensions for profiling. See [installing profilers] documentation.

## Configuration

If the URL is provided, data is submitted to the URL and data is available from
XHGui immediately.

Fallback is to use file saver, you need then manually transfer the file and
import it to XHGui.  See [import profiling] for details.

[XHGui]: https://github.com/perftools/xhgui
[installing profilers]: https://github.com/perftools/php-profiler#installing-profilers
[import profiling]: https://github.com/perftools/xhgui#profiling-a-web-request-or-cli-script
