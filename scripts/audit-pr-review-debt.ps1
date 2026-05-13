param(
    [string]$Owner = 'HomieEddy',
    [string]$Repo = 'NidVite',
    [int]$LimitPerBase = 60
)

$ErrorActionPreference = 'Stop'

$query = @'
query($owner:String!, $repo:String!, $base:String!, $limit:Int!) {
  repository(owner:$owner, name:$repo) {
    pullRequests(first:$limit, states:MERGED, baseRefName:$base, orderBy:{field:UPDATED_AT, direction:DESC}) {
      nodes {
        number
        title
        url
        mergedAt
        reviewThreads(first:100) {
          nodes {
            isResolved
            path
            line
            comments(first:20) {
              nodes {
                body
                author { login }
              }
            }
          }
        }
      }
    }
  }
}
'@

function Get-MergedPRsForBase {
    param([string]$Base)

    $payload = gh api graphql -f query="$query" -F owner=$Owner -F repo=$Repo -F base=$Base -F limit=$LimitPerBase | ConvertFrom-Json
    return @($payload.data.repository.pullRequests.nodes)
}

$allPrs = @(
    Get-MergedPRsForBase -Base 'develop'
    Get-MergedPRsForBase -Base 'main'
) | Select-Object -Unique -Property number, title, url, mergedAt, reviewThreads

$rows = foreach ($pr in $allPrs) {
    $threads = @($pr.reviewThreads.nodes)
    $unresolved = @($threads | Where-Object { -not $_.isResolved })

    $coderabbitUnresolved = @(
        $unresolved | Where-Object {
            @($_.comments.nodes.author.login) -match 'coderabbit'
        }
    )

    [pscustomobject]@{
        Number = $pr.number
        MergedAt = $pr.mergedAt
        UnresolvedThreads = $unresolved.Count
        CoderabbitUnresolvedThreads = $coderabbitUnresolved.Count
        Title = $pr.title
        Url = $pr.url
    }
}

$ranked = $rows |
  Sort-Object -Property @(
    @{ Expression = 'UnresolvedThreads'; Descending = $true },
    @{ Expression = 'CoderabbitUnresolvedThreads'; Descending = $true },
    @{ Expression = 'MergedAt'; Descending = $true }
  )

$reportPath = 'docs/process/RETROACTIVE_CODERABBIT_DEBT_REPORT.md'

$md = @()
$md += '# Retroactive Code Review Debt Report'
$md += ''
$md += "Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz')"
$md += ''
$md += "Scope: last $LimitPerBase merged PRs per base branch (develop and main)."
$md += ''
$md += '| PR | Merged At | Unresolved Threads | Unresolved CodeRabbit Threads | Title |'
$md += '|---|---|---:|---:|---|'

foreach ($r in $ranked) {
    $md += "| [#$($r.Number)]($($r.Url)) | $($r.MergedAt) | $($r.UnresolvedThreads) | $($r.CoderabbitUnresolvedThreads) | $($r.Title.Replace('|','/')) |"
}

Set-Content -Path $reportPath -Value ($md -join "`n") -Encoding utf8

Write-Host "Wrote $reportPath"
$ranked | Select-Object -First 20 | Format-Table -AutoSize

$detailPath = 'docs/process/RETROACTIVE_CODERABBIT_DEBT_DETAILS.md'
$detail = @()
$detail += '# Retroactive Code Review Debt Details'
$detail += ''
$detail += "Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz')"
$detail += ''

foreach ($pr in ($allPrs | Sort-Object mergedAt -Descending)) {
  $unresolved = @($pr.reviewThreads.nodes | Where-Object { -not $_.isResolved })
  if ($unresolved.Count -eq 0) {
    continue
  }

  $detail += "## [#$($pr.number)]($($pr.url)) - $($pr.title)"
  $detail += ''

  foreach ($thread in $unresolved) {
    $author = ''
    $excerpt = ''
    if (@($thread.comments.nodes).Count -gt 0) {
      $first = $thread.comments.nodes[0]
      $author = [string]$first.author.login
      $excerpt = [string]$first.body
      $excerpt = $excerpt.Replace("`r", ' ').Replace("`n", ' ')
      if ($excerpt.Length -gt 240) {
        $excerpt = $excerpt.Substring(0, 240) + '...'
      }
    }

    $detail += "- Path: $($thread.path), Line: $($thread.line), Author: $author"
    if ($excerpt -ne '') {
      $detail += "  Comment: $excerpt"
    }
  }

  $detail += ''
}

Set-Content -Path $detailPath -Value ($detail -join "`n") -Encoding utf8
Write-Host "Wrote $detailPath"
