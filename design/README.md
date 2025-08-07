# BOS Design Documentation

This directory contains comprehensive design documentation for the BOS (Business Operations System) project.

## Structure Overview

```
design/
├── README.md                    # This file - overview of design documentation
├── rules-api.md                 # Backend development rules (Laravel 12)
├── rules-app.md                 # Frontend development rules (Nuxt 4)
├── api/                         # Complete API documentation
│   ├── README.md                # API documentation overview
│   ├── index.md                 # GET list endpoints (pagination, search, filtering)
│   ├── show.md                  # GET single resource endpoints
│   ├── store.md                 # POST create endpoints
│   ├── update.md                # PUT/PATCH update endpoints
│   ├── destroy.md               # DELETE endpoints
│   ├── error.md                 # Error response format and handling
│   ├── auth/                    # Authentication endpoints
│   │   ├── README.md            # Authentication overview
│   │   ├── login.md             # POST /auth/login
│   │   ├── register.md          # POST /auth/register
│   │   ├── logout.md            # POST /auth/logout
│   │   ├── refresh.md           # POST /auth/refresh
│   │   └── status.md            # GET /auth/status
│   ├── resources/               # Resource-specific documentation
│   │   ├── README.md            # Resources overview
│   │   ├── users.md             # Users resource API
│   │   ├── products.md          # Products resource API
│   │   └── estimates.md         # Estimates resource API
│   └── app/                     # Application endpoints
│       ├── README.md            # Application endpoints overview
│       ├── menu.md              # GET /app/menu
│       └── documents.md         # Document generation APIs
├── app/                         # Frontend component designs (future)
├── dev/                         # Development tools and templates
└── template/                    # Template files for consistency
```

## Documentation Principles

### 1. Single Source of Truth
- Each aspect of the system has one authoritative documentation location
- API documentation in `design/api/` matches exactly what's implemented
- Rules files define mandatory development practices

### 2. Implementation-Driven
- Documentation reflects actual code implementation, not theoretical designs
- Database schemas, validation rules, and business logic documented from real models
- API endpoints documented from actual route definitions

### 3. Developer-Focused
- Clear examples for every endpoint and feature
- Validation rules and error responses documented
- Frontend integration patterns provided

### 4. Comprehensive Coverage
- Every API endpoint documented with request/response examples
- Authentication flows completely documented
- Error handling patterns and best practices included

## Quick Navigation

### For Backend Developers
- [Backend Rules](rules-api.md) - Mandatory development practices
- [API Documentation](api/README.md) - Complete API reference
- [Authentication](api/auth/README.md) - Auth system documentation

### For Frontend Developers  
- [Frontend Rules](rules-app.md) - Frontend development practices
- [API Integration](api/README.md) - How to consume the API
- [Error Handling](api/error.md) - Error response handling

### For API Consumers
- [API Overview](api/README.md) - Getting started with the API
- [Authentication](api/auth/README.md) - How to authenticate
- [Resources](api/resources/README.md) - Available data resources

## Key Features Documented

### Auto-Generated Resources
The BOS API uses an innovative auto-generation system where Eloquent models with the `#[ApiResource]` attribute automatically become full REST APIs:

- **Users** - User account management
- **Products** - Product catalog and inventory
- **Estimates** - Business estimates and quotations

### Service-Oriented Architecture
Business logic is organized into dedicated service classes:
- ResourceSearchService - Search functionality
- ResourceFilterService - Dynamic filtering
- ResourcePaginationService - Pagination logic
- ResourceSortingService - Multi-column sorting

### Standardized Responses
All API responses follow a consistent format with:
- Success/error indicators
- Human-readable messages
- Structured data and error details
- Metadata for UI generation

### Dynamic UI Generation
The API provides schema information for:
- Dynamic form generation
- Table column definitions
- Validation rules
- Filter options

## Maintenance

This documentation is maintained alongside code changes and follows these practices:

1. **API changes require documentation updates** - No API change is complete without updating the relevant documentation
2. **Examples are tested** - Code examples in documentation are validated against the actual API
3. **Regular reviews** - Documentation is reviewed for accuracy and completeness
4. **Version alignment** - Documentation version matches API implementation version

## Contributing

When making changes to the BOS system:

1. **Update documentation first** - Follow TDD principles for documentation
2. **Use existing patterns** - Follow established documentation patterns and structure
3. **Include examples** - Always provide practical examples
4. **Test documentation** - Verify examples work with the actual implementation

For questions about this documentation structure or specific API details, refer to the individual files or raise an issue in the project repository.