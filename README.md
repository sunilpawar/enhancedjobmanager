# Enhanced Job Manager for CiviCRM

A modern, feature-rich interface for managing CiviCRM scheduled jobs with advanced filtering, bulk operations, and real-time status monitoring.

## Overview

The Enhanced Job Manager extension transforms the default CiviCRM scheduled jobs interface into a powerful management tool that provides administrators with better visibility and control over their automated processes. This extension is essential for organizations running multiple scheduled jobs and needing enhanced monitoring capabilities.

### Key Features

- **Advanced Filtering**: Filter jobs by status, frequency, domain, and custom criteria
- **Bulk Operations**: Perform actions on multiple jobs simultaneously (enable/disable, run, delete)
- **Real-time Status Monitoring**: Live updates on job execution status and last run times
- **Enhanced Job Details**: Detailed information about each job including execution history
- **Improved User Interface**: Modern, responsive design with better usability
- **Quick Actions**: One-click enable/disable, run now, and configuration access
- **Search Functionality**: Quickly find specific jobs by name or description
- **Job Performance Metrics**: Track execution times and success rates

## Why Use Enhanced Job Manager?

- **Better Visibility**: Get a comprehensive overview of all scheduled jobs at a glance
- **Improved Efficiency**: Manage multiple jobs quickly with bulk operations
- **Enhanced Monitoring**: Real-time status updates help identify issues faster
- **User-Friendly**: Intuitive interface reduces training time for administrators
- **Troubleshooting**: Detailed logs and metrics help diagnose job issues

## Requirements

- CiviCRM 5.81 or higher
- PHP 8.1 or higher

## Installation

### Method 1: Manual Installation

1. Download the latest release from the [GitHub releases page](https://github.com/skvare/enhancedjobmanager/releases)
2. Extract the files to your CiviCRM extensions directory
3. Navigate to **Administer** → **System Settings** → **Extensions**
4. Find "Enhanced Job Manager" in the list and click **Install**

### Method 2: Git Installation (Development)

```bash
cd /path/to/civicrm/extensions
git clone https://github.com/skvare/enhancedjobmanager.git
```

Then install through the CiviCRM Extensions interface.

## Configuration

After installation, the Enhanced Job Manager will replace the default scheduled jobs interface.

### Initial Setup

1. Navigate to **Administer** → **System Settings** → **Scheduled Jobs**
2. The enhanced interface will load automatically
3. Configure the extension settings as needed:
   - Enable/disable debug mode
   - Set default job filters
   - Customize job display options
4. Save changes

### Permissions

Grant the following permissions to appropriate user roles:

- `administer CiviCRM` - Required for job management

## Usage

### Job Management Interface

The enhanced interface provides several views:

#### Dashboard View
- Overview of all jobs with status indicators
- Quick stats: total jobs, active jobs, failed jobs, next scheduled run
- Recent job log

#### List View
- Comprehensive table of all scheduled jobs
- Sortable columns: Name, Status, Frequency, Last Run, Next Run
- Inline actions: Enable/Disable, Run Now, Edit, Delete

#### Detailed View
- In-depth information for individual jobs
- Execution history and logs
- Performance metrics and charts

### Advanced Filtering

Filter jobs by:
- **Status**: Active, Inactive, Running, Failed
- **Frequency**: Hourly, Daily, Weekly, Monthly, Always, Never
- **Last Run**: Date ranges
- **Custom Tags**: User-defined job categories

### Bulk Operations

Select multiple jobs to:
- Enable or disable simultaneously
- Run multiple jobs at once
- Delete multiple jobs (with confirmation)
- Apply bulk settings changes

## Features in Detail

### Real-time Monitoring

- Live job status updates without page refresh
- Progress indicators for running jobs
- Automatic refresh of last run times
- Color-coded status indicators

### Enhanced Logging

- Detailed execution logs with timestamps
- Error messages and stack traces
- Performance metrics (execution time, Success rate,  Error Rate, Avg Duration)

### Reporting and Analytics

- Job performance dashboards
- Success/failure rate tracking
- Execution time trends
- Resource usage monitoring

## Troubleshooting

### Common Issues

**Jobs not appearing in the interface:**
- Verify the extension is enabled
- Check user permissions
- Clear CiviCRM caches

**Real-time updates not working:**
- Ensure JavaScript is enabled
- Check browser console for errors
- Verify AJAX endpoints are accessible

**Bulk operations failing:**
- Check PHP memory limits
- Verify database connection stability
- Review CiviCRM error logs

### Debug Mode

Enable debug mode for additional logging:

1. Navigate to **Administer** → **System Settings** → **Enhanced Job Manager Settings**
2. Enable "Debug Mode"
3. Check logs in `ConfigAndLog/CiviCRM.*.log`

## Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Setup

1. Fork the repository
2. Clone your fork locally
3. Create a feature branch
4. Make your changes
5. Submit a pull request

### Coding Standards

- Follow CiviCRM coding standards
- Include unit tests for new functionality
- Update documentation as needed
- Ensure backward compatibility

## Support

- **Documentation**: [Extension Documentation](https://github.com/skvare/enhancedjobmanager/wiki)
- **Issues**: [GitHub Issues](https://github.com/skvare/enhancedjobmanager/issues)
- **Community**: [CiviCRM Chat](https://chat.civicrm.org)
- **Professional Support**: Contact [Skvare](https://skvare.com/contact)

## License

This extension is licensed under [AGPL-3.0](LICENSE.txt).

## Credits

- **Author**: Sunil Pawar
- **Organization**: [Skvare](https://skvare.com)

## Related Extensions

- [CiviCRM Core Scheduled Jobs](https://docs.civicrm.org/sysadmin/en/latest/setup/jobs/)
- [Job Scheduler](https://civicrm.org/extensions/job-scheduler)
- [Advanced Logging](https://civicrm.org/extensions/advanced-logging)

---

**Made with ❤️ for the CiviCRM Community**

For more information about CiviCRM extensions, visit the [Skvare](https://skvare.com/contact).
