#!/bin/bash
SESSION_NAME="freekill"
TEMP_FILE="$(mktemp /tmp/screen_output.XXXXXX)"
OUTPUT_FILE="/tmp/2hu_output.txt"

# # 确保退出时删除临时文件
# cleanup() {
#   rm -f "$TEMP_FILE"
#   # 不删除输出文件，让PHP可以读取
#   chmod 644 "$OUTPUT_FILE"
# }
# trap cleanup EXIT

# 检查会话是否存在
if ! screen -list | grep -q "$SESSION_NAME"; then
  echo "ERROR: Screen session '$SESSION_NAME' not found." | tee "$OUTPUT_FILE"
  exit 1
fi

# 执行命令函数（移除重试机制）
run_screen_cmd() {
  local cmd="$1"
  screen -S "$SESSION_NAME" -X stuff "$cmd"$'\n'
  sleep 1.5
  # 使用绝对路径确保权限正确
  screen -S "$SESSION_NAME" -X hardcopy "$TEMP_FILE"
}

# 开始收集输出
{
  # 执行 u 2hu
  run_screen_cmd "u 2hu"
  echo "u 2hu 执行结果:"
  u2hu_output=$(grep -A 5 "Running command: \"u 2hu\"" "$TEMP_FILE")
  echo "$u2hu_output"
  
  echo -e "\n当前版本："
  # 执行 pkgs
  run_screen_cmd "pkgs"
  twohu_line=$(grep -E "2hu\s+[0-9a-f]{8}\s+1" "$TEMP_FILE")
  echo "$twohu_line"
  
  # 添加屏幕内容的调试信息
  echo -e "\n--- Screen内容摘要 ---"
  head -n 20 "$TEMP_FILE"
} > "$OUTPUT_FILE" 2>&1

# 同时输出到标准输出，方便直接运行脚本时观察
cat "$OUTPUT_FILE"

exit 0
