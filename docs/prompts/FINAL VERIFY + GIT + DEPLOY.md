# FINAL VERIFY + GIT + DEPLOY

Changes အားလုံးပြီးပါက production readiness ကို verify လုပ်ပါ။

Run applicable:

- Build
- Lint
- Type check
- Unit tests
- Integration tests
- Critical smoke tests

Failed tests ရှိပါက reason မသိဘဲ production deploy မလုပ်ပါနှင့်။

Diff ကို review လုပ်ပြီး:

- Debug leftovers
- Secrets
- Temporary files
- Accidental generated files
- Unrelated changes

မပါကြောင်းစစ်ပါ။

Changes ကို logical Git commit ဖြင့် commit လုပ်ပါ။

Configured remote repository နှင့် authorization ရှိပါက push လုပ်ပါ။

Deployment environment/access ရှိပါက configured hosting သို့ deploy လုပ်ပါ။

Deploy ပြီးနောက် production/staging URL တွင် health check နှင့် critical flows ပြန်စစ်ပါ။

မလုပ်နိုင်သော action ကို လုပ်ပြီးပြီဟုမပြောပါနှင့်။

Final report:

- Commit hash
- Branch
- Push status
- Deployment status
- Build/test status
- Deployment URL if available
- Remaining blockers/issues