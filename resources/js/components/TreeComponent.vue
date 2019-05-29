<template>
  <div id="container"></div>
</template>

<script>
import * as d3 from 'd3';
  export default {
    props: ['treeData'],
    beforeDestroy() {
      d3.selectAll('.div-scroll').remove();
    },
    created() {
      if (this.treeData.lenght == 0) {
        return;
      }
      var treeData = this.treeData;
      var _default = {
        width: 3000,
        height: 700,
        boxWidth: 150,
        boxHeight: 25
      }

      function structureGraph(data, options) {
        options = options || _default
        this.data = data
        this.width = options.width
        this.height = options.height
        this.boxWidth = options.boxWidth
        this.boxHeight = options.boxHeight
      }

      structureGraph.prototype.initLayout = function () {
        this.svg = d3.select('body')
          .append('div')
          .attr('class', 'div-scroll')
          .append('svg')
          .attr('class', 'structure-graph')
          .attr('width', this.width)
          .attr('height', this.height + 200)

        this.chart = this.svg.append('g')
          .attr('class', 'chart-layer')
          .attr('transform', 'translate(50,30)')

        this.chart.append('g')
          .attr('class', 'links')

        this.chart.append('g')
          .attr('class', 'nodes')

        return this
      }

      structureGraph.prototype.preprocessData = function () {
        this.data = d3.hierarchy(this.data, function (d) {
          return d.children
        })
        var structureGenerator = d3.tree()
          .size([this.width - 5, this.height - 5]) // 树布局的尺寸
          // .nodeSize([1,1]) // 节点的尺寸
          .separation(function (a, b) {
            return a.parent == b.parent ? 1 : 1
          }) // 间隔访问器用来设置相邻的两个节点之间的间隔。指定的间隔访问器会传递两个节点 a 和 b，并且必须返回一个期望的间隔值。

        structureGenerator(this.data)
        // 增加坐标值
        // x: 0.5
        // y: 0

        this.nodes = this.data.descendants() // 获取所有的节点数组
        this.links = this.data.links() // 获取所有的相连边
        // 每条边包含 target 和 source 属性

        return this
      }

      structureGraph.prototype.renderGraph = function () {
        var _this = this
        // 先画曲线，防止曲线覆盖节点
        d3.select('.links')
          .selectAll(".link")
          .data(this.links)
          .enter()
          .append("path")
          .attr('class', 'link')
          .attr("fill", "none")
          .attr("stroke", "#555")
          .attr("stroke-width", 1)
          .attr("d", linkGenerator)

        
        function linkGenerator(d) {
          let sourceX = d.source.x,
            sourceY = d.source.y,
            targetX = d.target.x,
            targetY = d.target.y;

          return "M" + sourceX + "," + sourceY +
            "V" + ((targetY - sourceY) / 2 + sourceY) +
            "H" + targetX +
            "V" + targetY;

        }

        // 画节点
        d3.select('.nodes')
          .selectAll('.node')
          .data(this.nodes)
          .enter().append('g')
          .attr('class', 'node')
          .attr('transform', function (d) {
            return 'translate(' + d.x + ',' + d.y + ')'
          })
          .append('rect')
          .attr('width', this.boxWidth)
          .attr('height', this.boxHeight)
          .attr('transform', 'translate('+ (-this.boxWidth/2) +','+ (-this.boxHeight/2) +')')
          .style('stroke-width', '1')
          .style('stroke', 'steelblue')
          .style('fill', 'white')

        d3.selectAll('.node')
          .append('text')
          .text(function (d) {
            if (d.data.isLeaf) {
              return `${d.data.class} ${d.data.confidence}`;
            } else {
              return `<= (${d.data.attributeName}=${d.data.value}) >`
            }
          })
          // .attr("dy", "0.31em")
          // .attr("x", d => d.children ? -6 : 6)
          .style('text-anchor', 'middle') // 文字的水平居中
          .style('dominant-baseline', 'middle') // 文字的垂直居中
          .attr("fill", "black")
      }

      structureGraph.prototype.init = function () {
        this.initLayout()
          .preprocessData()
          .renderGraph()
      }

      var struct = new structureGraph(treeData)

      struct.init()
      
  }
    
}
</script>

<style>
  .div-scroll {
    overflow: scroll;
  }
</style>
