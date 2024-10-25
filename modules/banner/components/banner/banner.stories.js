import Banner from "./banner.twig"
import "./banner.css"
import { Heading, Subheading } from "@storybook/blocks"

export default {
  title: "Components/Banner",
  tags: ["autodocs"],
  argTypes: {
    banner_heading: {
      control: { type: "text" },
    },
    banner_body: {
      control: { type: "text" },
    },
    background_image: {
      control: { type: "text" },
    },
  },
  component: Banner,
}

export const Primary = {
  args: {
    banner_heading: "Main Heading",
    banner_body: "Subheading goes here",
    background_image: "https://via.placeholder.com/150",
  },
}

