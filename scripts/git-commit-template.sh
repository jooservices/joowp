#!/bin/bash
# Helper script to generate commit message with metadata template
# Usage: ./scripts/git-commit-template.sh

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Git Commit Message Template Generator"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "Enter commit type (feat/fix/docs/test/refactor/style/chore):"
read COMMIT_TYPE

echo "Enter scope (e.g., core, wordpress, app, docs):"
read SCOPE

echo "Enter description:"
read DESCRIPTION

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Metadata (REQUIRED for AI-generated commits)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "Enter Generated-By (e.g., Cursor Pro, ChatGPT Plus):"
read GENERATED_BY

echo "Enter Generated-By-Tool (e.g., Cursor Pro, GitHub Copilot):"
read GENERATED_BY_TOOL

echo "Enter Model (e.g., Auto, gpt-4o, claude-sonnet-3.5):"
read MODEL

echo "Enter Task-ID (or N/A):"
read TASK_ID

echo "Enter Plan path (or N/A):"
read PLAN

echo "Enter Coverage (e.g., 95%, N/A, Documentation):"
read COVERAGE

# Generate commit message
COMMIT_MSG="${COMMIT_TYPE}(${SCOPE}): ${DESCRIPTION}

Generated-By: ${GENERATED_BY}
Generated-By-Tool: ${GENERATED_BY_TOOL}
Model: ${MODEL}
Task-ID: ${TASK_ID}
Plan: ${PLAN}
Coverage: ${COVERAGE}"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Generated commit message:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "$COMMIT_MSG"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Copy above message for: git commit -m \"...\""
echo "Or use: git commit -F - (then paste message)"

