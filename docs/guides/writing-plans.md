# Guide: Writing Feature Plans

This guide defines the standard format for feature implementation plans in the JOOservices platform.

## Plan Template

```markdown
# Plan – [Feature Name]

Status: Draft  
Priority: P2  
Owner: Viet Vu <jooservices@gmail.com>  
Created: YYYY-MM-DD  
Updated: YYYY-MM-DD  
Target: YYYY-MM-DD  
Epic: [Feature Group Name]

## Summary
[2-3 sentence overview of what this feature delivers and why it matters]

**Scope:** [What's included/excluded, phase boundaries if applicable]

## Dependencies
- [Prerequisite system/service that must be ready]
- [External dependency or blocker]
- [Required infrastructure or configuration]

## Objectives
- [Measurable outcome 1 with specific metric]
- [Measurable outcome 2 with specific metric]
- [Measurable outcome 3 with specific metric]

## Tasks
- [ ] Task 1
  - DoD: [Specific criteria that proves this task is complete]
  - DoD: [Additional verification criteria]
  - Estimated: X hours
- [ ] Task 2
  - DoD: [Completion criteria]
  - DoD: [Test coverage requirement]
  - Estimated: X hours
- [ ] Task 3
  - DoD: [Completion criteria]
  - DoD: [Documentation requirement]
  - Estimated: X hours

**Total Estimated Effort:** XX hours (~X weeks for Y developers)

## Success Metrics
- **[Category]:** [Measurable target with threshold]
- **[Category]:** [Measurable target with threshold]
- **[Category]:** [Measurable target with threshold]

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| [Risk description] | High/Medium/Low | High/Medium/Low | [How to prevent/handle it] |
| [Risk description] | High/Medium/Low | High/Medium/Low | [How to prevent/handle it] |

## Related Plans
- `[path/to/related-plan.md]` - [Brief description of relationship]
- `[path/to/dependency.md]` - [Brief description of dependency]
- Phase 2: [Future work description] (separate plan TBD)

## Notes
- [Technical constraints, dependencies, or important context]
- [Links to related documentation or issues]
- [Future enhancement ideas]
```

## Status Values

| Status | When to Use | Next Action |
|--------|-------------|-------------|
| **Draft** | Initial planning, requirements unclear | Refine objectives, gather feedback |
| **Ready** | Plan approved, ready to start | Assign owner, begin implementation |
| **Active** | Work in progress | Complete tasks, update status regularly |
| **Blocked** | Cannot proceed due to external dependency | Document blocker, escalate if needed |
| **Review** | Implementation complete, awaiting approval | Code review, QA testing |
| **Completed** | All tasks done, feature shipped | Archive plan, document lessons learned |
| **Cancelled** | No longer needed or deprioritized | Document reason, move to retrospectives |

## Priority Values

| Priority | Description | SLA |
|----------|-------------|-----|
| **P0 - Critical** | Production down, security vulnerability | Start immediately, all-hands |
| **P1 - High** | Major feature, blocking other work | Start within 1 day |
| **P2 - Medium** | Standard feature work | Start within 1 week |
| **P3 - Low** | Nice-to-have, technical debt | Backlog, plan in next sprint |
| **P4 - Future** | Exploratory, long-term vision | Revisit quarterly |

## Writing Guidelines

### Summary Section
- **Be concise**: 2-3 sentences maximum
- **Answer "why"**: Explain business value or problem solved
- **Include scope**: What's explicitly included/excluded, phase boundaries

**Example:**
```markdown
## Summary
Enable content creators to generate AI-powered article outlines, introductions, 
and meta descriptions directly in the WordPress post editor. All AI operations 
use approved local models (Ollama) with mandatory manual review before publishing.

**Scope:** Phase 1 - Core content generation only (outline, intro, meta). 
Phase 2 will include fact-checking and summaries.
```

### Dependencies Section
- **List prerequisites**: What must exist before work can start
- **External blockers**: Third-party systems, approvals, infrastructure
- **Internal dependencies**: Other features, modules, or services

**Example:**
```markdown
## Dependencies
- Ollama installation on development/production servers
- WordPress SDK authentication (wp_tokens table)
- Action logging infrastructure (`ActionLogger`)
- DevOps approval for production deployment
```

### Objectives Section
- **Make them measurable**: Use numbers, percentages, or specific outcomes
- **Focus on impact**: What changes for users or the business
- **Limit to 3-5 items**: Too many objectives = unclear scope

**Good objectives:**
- ✅ Reduce post creation time by 30% for editors
- ✅ Achieve 80% test coverage on all AI service endpoints
- ✅ Support 5 content generation templates (outline, intro, conclusion, meta, summary)

**Bad objectives:**
- ❌ Make the system better
- ❌ Improve performance
- ❌ Add AI features

### Tasks Section
- **Start with verb**: "Create", "Implement", "Update", "Test"
- **One task = one pull request**: If task is too big, split it
- **Include DoD (Definition of Done)**: Specific, testable criteria
- **Add time estimates**: For sprint planning and capacity management
- **Order by dependency**: Blocked tasks go last
- **Check off when complete**: Update with `[x]` when done
- **Calculate total effort**: Sum estimates for project planning

**Task structure:**
```markdown
- [ ] Create CategoryService for business logic
  - DoD: Service implements all CRUD methods (list, create, update, delete)
  - DoD: 95% test coverage with unit + feature tests
  - DoD: Audit logging added for all mutations
  - DoD: PHPStan passes at maximum level
  - Estimated: 6 hours

**Total Estimated Effort:** 87 hours (~2-3 weeks for 1 developer)
```

**Definition of Done (DoD) Guidelines:**
- Code quality: "Passes `composer lint` pipeline"
- Testing: "80% coverage with passing tests"
- Documentation: "API endpoints documented in guide"
- Integration: "Works with existing WordPress SDK"
- Performance: "Response time < 200ms for list operations"
- Security: "FormRequest validation for all inputs"

### Success Metrics Section
- **Make them quantifiable**: Numbers, percentages, time thresholds
- **Use categories**: Performance, Adoption, Quality, Reliability, Security
- **Set realistic targets**: Based on baseline or industry standards
- **Include measurement method**: How will you verify the metric

**Example:**
```markdown
## Success Metrics
- **Performance:** AI generation completes in < 10 seconds for 95% of requests
- **Adoption:** 50% of content editors try AI features within first month
- **Quality:** 80% of generated content accepted with minor edits
- **Reliability:** 99% uptime for AI service endpoints
- **Security:** Zero instances of sensitive data leakage in prompts
```

### Risks & Mitigations Section
- **Identify potential problems**: Technical, resource, timeline, quality
- **Assess impact**: What happens if this risk materializes
- **Estimate probability**: How likely is this to occur
- **Define mitigation**: Concrete actions to prevent or handle the risk

**Example:**
```markdown
## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Ollama performance issues | High | Medium | Load test early, implement request queuing |
| Poor content quality | Medium | High | Extensive prompt engineering, user feedback loop |
| Resource exhaustion | High | Low | Rate limiting, request timeouts, monitoring |
| User distrust of AI | Medium | Medium | Clear labeling, manual review required |
```

### Related Plans Section
- **Link dependencies**: Plans that must complete first
- **Link integration points**: Plans that share systems or features
- **Link epic siblings**: Other plans in same feature group
- **Note future phases**: Work that will continue in separate plans

**Example:**
```markdown
## Related Plans
- `docs/features/wordpress/posts-management.md` - Post editor integration point
- `docs/technical/caching-strategy.md` - May cache AI responses for similar prompts
- Phase 2: Fact-checking expansion (separate plan TBD)
```

### Notes Section
Use for:
- **Technical constraints**: "Must use Ollama local models only"
- **Dependencies**: "Blocked by WordPress SDK authentication (#42)"
- **Risks**: "High memory usage if processing large posts"
- **Future work**: "Consider adding translation support in v2"
- **Related docs**: "See `docs/architecture/flow.md` for service layer pattern"

## Workflow

### 1. Creating a New Plan
```bash
# Create plan file in appropriate directory
touch docs/features/wordpress/my-feature.md
# or
touch docs/technical/performance-optimization.md
```

**File naming:**
- Use descriptive kebab-case: `media-management.md`, `api-caching.md`
- No date prefixes (git history tracks creation date)
- Group by domain: `features/wordpress/`, `technical/`, `features/ai/`

### 2. During Implementation
- **Update status** as work progresses: Draft → Ready → Active → Review → Completed
- **Check off tasks** when DoD criteria are met: `- [x] Task completed`
- **Update "Updated" date** when making significant changes
- **Add notes** for blockers or scope changes

### 3. Completing a Plan
When all tasks are checked:
1. Update status to **Completed**
2. Update "Updated" date
3. Create retrospective if significant learnings: `docs/retrospectives/feature-name.md`
4. Move plan to archive if desired (optional): `docs/archive/features/`

## Examples

### Good Plan - Clear and Actionable
```markdown
# Plan – WordPress Post Scheduling

Status: Active  
Priority: P1  
Owner: Viet Vu <jooservices@gmail.com>  
Created: 2025-11-13  
Updated: 2025-11-13  
Target: 2025-11-30  
Epic: WordPress Features

## Summary
Enable content editors to schedule WordPress posts for future publication 
directly from the JOOservices platform. Eliminates manual publishing 
coordination and reduces missed publication deadlines.

**Scope:** Single post scheduling only. Bulk scheduling and recurring 
schedules are out of scope for Phase 1.

## Dependencies
- WordPress SDK with authentication (completed)
- Laravel queue worker running in production
- Database migration for scheduled_posts table

## Objectives
- Support scheduling up to 30 days in advance
- Achieve 99.9% on-time publication accuracy
- Reduce manual publish operations by 80%

## Tasks
- [x] Add scheduled_posts table migration
  - DoD: Columns: post_id, publish_at, status, retry_count
  - DoD: Indexes on publish_at and status
  - DoD: Foreign key to wp_posts (if stored locally)
  - Estimated: 2 hours
- [ ] Create ScheduledPostService with queue integration
  - DoD: Implements schedule(), reschedule(), cancel() methods
  - DoD: 90% test coverage with edge case handling
  - DoD: Uses Laravel queue for reliable execution
  - Estimated: 8 hours
- [ ] Add schedule UI to post editor
  - DoD: Date/time picker with timezone support
  - DoD: Shows scheduled posts in separate tab
  - DoD: Allows rescheduling and cancellation
  - Estimated: 10 hours
- [ ] Implement queue job for publishing
  - DoD: Retries 3 times on failure with exponential backoff
  - DoD: Sends notification on success/failure
  - DoD: Updates post status in local database
  - Estimated: 6 hours

**Total Estimated Effort:** 26 hours (~1 week for 1 developer)

## Success Metrics
- **Reliability:** 99.9% of scheduled posts publish within 1 minute of target time
- **Adoption:** 30% of posts use scheduling within first 2 weeks
- **Error Rate:** < 0.1% failed publications requiring manual intervention

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Queue worker downtime | High | Low | Implement health checks, alerting, auto-restart |
| Timezone confusion | Medium | High | Store UTC, display user TZ, prominent TZ indicator in UI |
| WordPress API throttling | Medium | Low | Rate limiting, retry with backoff, queue spreading |

## Related Plans
- `docs/features/wordpress/posts-management.md` - Post editor integration
- Phase 2: Bulk scheduling and recurring posts (Q1 2026)

## Notes
- Uses Laravel queue system - ensure queue worker is running
- Timezone handling: Store in UTC, display in user's timezone
- Consider future enhancement: Recurring schedules (weekly series)
```

### Bad Plan - Vague and Incomplete
```markdown
# Plan – Improve System

Status: Active  
Owner: TBD  

## Summary
Make things better

## Objectives
- Improve performance
- Add features
- Fix bugs

## Tasks
- [ ] Work on backend
- [ ] Update frontend
- [ ] Test everything

## Notes
None
```

## Integration with Development Workflow

### Link Plans to Code
Reference plan in commit messages:
```bash
git commit -m "feat(wordpress): add post scheduling service

Implements scheduled post creation and queue job
for automated publishing at specified times.

Generated-By: Cursor Pro
Generated-By-Tool: Cursor Pro
Model: Auto
Task-ID: WP-5
Plan: docs/plans/features/2025-11-14-post-scheduling.md
Coverage: 90%"
```

### Link Plans to Pull Requests
Include plan link in PR description:
```markdown
## Description
Implements scheduled post publishing using Laravel queues.

## Plan
See: `docs/features/wordpress/post-scheduling.md`

## Completed Tasks
- [x] Create ScheduledPostService with queue integration
- [x] Add schedule UI to post editor
```

### Track Progress
Use GitHub issues/projects to track plan status:
- One issue per plan
- Label by priority: `P1-high`, `P2-medium`, `P3-low`
- Link PRs to plan issue
- Close issue when status = Completed

## Common Mistakes to Avoid

❌ **No Definition of Done**
```markdown
- [ ] Create service
- [ ] Add tests
```

✅ **Clear DoD**
```markdown
- [ ] Create CategoryService with CRUD operations
  - DoD: Implements list(), create(), update(), delete()
  - DoD: 95% test coverage
  - DoD: Audit logging for mutations
```

❌ **Vague Objectives**
```markdown
## Objectives
- Make it work better
- Improve UX
```

✅ **Measurable Objectives**
```markdown
## Objectives
- Reduce post creation time from 10min to 7min (30% improvement)
- Achieve 80% user satisfaction score in post-release survey
```

❌ **Too Many Tasks**
```markdown
## Tasks
- [ ] Task 1
- [ ] Task 2
... (30 tasks total)
```

✅ **Focused Scope**
```markdown
## Tasks
- [ ] Task 1
- [ ] Task 2
- [ ] Task 3
- [ ] Task 4

## Notes
- Phase 2 tasks tracked in separate plan: docs/features/feature-phase2.md
```

## References

- See `docs/architecture/principles.md` for engineering standards
- See `docs/development/code-quality.md` for testing requirements
- See existing plans in `docs/features/` for examples
- See `docs/decisions/` for Architecture Decision Records (ADRs)
