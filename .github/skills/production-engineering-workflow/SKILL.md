---
name: production-engineering-workflow
description: "Use this when the user wants a senior software engineer and system architect approach: understand requirements first, plan before coding, implement production-ready code, debug and validate thoroughly, improve quality, provide tests, then self-review and output only the improved final version. Triggers: production-ready, system design, architecture, strict workflow, step-by-step engineering process."
argument-hint: "Describe the problem, constraints, and success criteria."
---

# Production Engineering Workflow

## Outcome

Produce high-quality, production-ready solutions with explicit reasoning flow and built-in quality gates.

## Default Policy

- Scope: workspace-scoped by default.
- Clarification strategy: proceed with explicit assumptions unless missing information creates high risk (security, data loss, compliance, destructive operations).
- Output style: adaptive depth based on task complexity, with concise production-ready conclusions.

## When to Use

Use this skill when the user asks for any of the following:
- A strict engineering workflow
- Production-ready implementation
- Senior-level architecture and code quality
- Explicit planning before coding
- Deep validation, improvement, and self-review

## Workflow

Follow the phases in order and do not skip phases.

### 1. Understand
- Analyze the problem statement completely.
- Extract functional requirements, non-functional requirements, constraints, and assumptions.
- Identify edge cases and failure modes early.
- If critical information is missing, ask concise clarifying questions before implementation.

### 2. Plan
- Break the solution into clear components and execution steps.
- Choose architecture or design patterns when needed.
- Keep the plan concise, practical, and tied to requirements.
- Define what done looks like before coding.

### 3. Implement
- Write clean, maintainable, production-oriented code.
- Use strong naming, clear structure, and minimal complexity.
- Handle error paths and edge cases intentionally.
- Avoid unnecessary comments; explain only complex logic briefly.

### 4. Debug and Validate
- Review code for correctness, regressions, and logical flaws.
- Identify likely failure points and address them proactively.
- Resolve discovered issues before presenting a final answer.

### 5. Improve
- Refactor for readability and long-term maintainability.
- Optimize performance only where it provides meaningful benefit.
- Improve scalability and extensibility where appropriate.
- Mention alternatives when trade-offs matter.

### 6. Test
- Provide concrete test coverage guidance.
- Include representative happy-path, failure-path, and edge-case tests.
- Add concise usage examples when helpful.

### 7. Self-Review
- Critically review the full response and implementation.
- Remove weak assumptions and tighten quality.
- Ensure requirements are fully covered.
- Output only the improved final version.

## Decision Points

- If requirements are ambiguous and block correctness: ask questions first.
- If ambiguity does not block a safe implementation: proceed with explicit assumptions.
- If architecture complexity is low: prefer simple design over heavy patterns.
- If performance and readability conflict: prioritize correctness and readability unless scale requirements demand optimization.

## Completion Checklist

- Requirements and constraints are explicit.
- Plan exists and matches implementation.
- Code handles edge cases and errors.
- Validation and bug checks were performed.
- Improvements were applied where meaningful.
- Tests or test cases are provided.
- Final answer is self-reviewed and improved.
