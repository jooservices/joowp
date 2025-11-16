import fs from 'fs';
import path from 'path';
import axios from 'axios';
import dotenv from 'dotenv';

dotenv.config();

/**
 * ENV required:
 * JIRA_DOMAIN
 * JIRA_EMAIL
 * JIRA_API_TOKEN
 * JIRA_PROJECT_KEY
 */

const jira = axios.create({
  baseURL: `https://${process.env.JIRA_DOMAIN}/rest/api/3`,
  auth: {
    username: process.env.JIRA_EMAIL,
    password: process.env.JIRA_API_TOKEN
  },
  headers: { "Accept": "application/json", "Content-Type": "application/json" }
});

async function createIssue(fields) {
  const res = await jira.post('/issue', { fields });
  return res.data;
}

// ----------------------------
// ADF (Atlassian Document Format) Converter
// ----------------------------

function toADF(text) {
  return {
    type: "doc",
    version: 1,
    content: [
      {
        type: "paragraph",
        content: text
          ? [{ type: "text", text }]
          : []
      }
    ]
  };
}

// ----------------------------
// Markdown Parsing
// ----------------------------

function parsePlan(markdown) {
  const lines = markdown.split('\n');

  let epic = null;
  let currentStory = null;

  const stories = [];

  for (let line of lines) {
    const trimmed = line.trim();

    // Epic = "# Plan – XXXX"
    if (trimmed.startsWith('# Plan –')) {
      epic = {
        summary: trimmed.replace('# Plan –', '').trim(),
        description: ''
      };
      continue;
    }

    // Story = "## Phase X – ..."
    if (trimmed.startsWith('## ')) {
      currentStory = {
        summary: trimmed.replace('## ', '').trim(),
        description: '',
        subtasks: []
      };
      stories.push(currentStory);
      continue;
    }

    // Sub-tasks
    if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
      if (currentStory) {
        const clean = trimmed
          .replaceAll('- [x] ', '')
          .replaceAll('- [ ] ', '')
          .replaceAll('* [x] ', '')
          .replaceAll('* [ ] ', '')
          .replaceAll('- ', '')
          .replaceAll('* ', '')
          .trim();

        // Only add non-empty subtasks
        if (clean) {
          currentStory.subtasks.push(clean);
        }
      }
      continue;
    }

    // Description
    if (epic && !currentStory) {
      epic.description += line + '\n';
    } else if (currentStory) {
      currentStory.description += line + '\n';
    }
  }

  return { epic, stories };
}

// ----------------------------
// Jira Push Logic
// ----------------------------

async function pushToJira(planFile) {
  const markdown = fs.readFileSync(planFile, 'utf8');
  const { epic, stories } = parsePlan(markdown);

  if (!epic) {
    console.error('❌ Error: Plan file must contain a line starting with "# Plan –" to create an Epic.');
    process.exit(1);
  }

  console.log(`➡ Creating Epic: ${epic.summary}`);

  const epicRes = await createIssue({
    project: { key: process.env.JIRA_PROJECT_KEY },
    summary: epic.summary,
    description: toADF(epic.description),
    issuetype: { name: "Epic" },
    customfield_10011: epic.summary // Epic Name
  });

  console.log(`   ✔ Epic created: ${epicRes.key}`);

  // Push Stories
  for (const story of stories) {
    console.log(`➡ Creating Story: ${story.summary}`);

    const storyRes = await createIssue({
      project: { key: process.env.JIRA_PROJECT_KEY },
      summary: story.summary,
      description: toADF(story.description),
      issuetype: { name: "Story" },
      parent: { key: epicRes.key }
    });

    console.log(`   ✔ Story created: ${storyRes.key}`);

    // Sub-tasks
    for (const sub of story.subtasks) {
      // Skip empty subtasks (defensive check)
      if (!sub || !sub.trim()) {
        continue;
      }

      console.log(`     ➡ Creating Sub-task: ${sub}`);

      await createIssue({
        project: { key: process.env.JIRA_PROJECT_KEY },
        summary: sub,
        issuetype: { name: "Sub-task" },
        parent: { key: storyRes.key },
        description: toADF(sub)
      });
    }
  }

  console.log("🎉 Done! All issues pushed to Jira.");
}

// ----------------------------
// Run
// ----------------------------

const file = process.argv[2];
if (!file) {
  console.error("Usage: node push-to-jira.js <markdown-file>");
  process.exit(1);
}

(async () => {
  try {
    await pushToJira(path.resolve(file));
  } catch (err) {
    console.error('❌ Error:', err.message);
    if (err.response) {
      console.error('Jira API Error:', err.response.status, err.response.data);
    }
    process.exit(1);
  }
})();